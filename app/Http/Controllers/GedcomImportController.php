<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GedcomImportController extends Controller
{
    public function index(): View
    {
        return view('genealogy.import-gedcom');
    }

    /**
     * Parse GEDCOM file → trả JSON preview (chưa lưu vào DB).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'gedcom' => ['required', 'file', 'max:10240', 'mimes:ged,txt'],
        ]);

        $content  = file_get_contents($request->file('gedcom')->getRealPath());
        // Normalize encoding: GEDCOM thường UTF-8 hoặc ANSI
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        ['individuals' => $inds, 'families' => $fams] = $this->parseGedcom($content);

        if (empty($inds)) {
            return response()->json(['error' => 'Không tìm thấy thành viên nào trong file GEDCOM.'], 422);
        }

        $preview = array_slice(array_values($inds), 0, 15);

        return response()->json([
            'total_individuals' => count($inds),
            'total_families'    => count($fams),
            'preview'           => $preview,
        ]);
    }

    /**
     * Import GEDCOM → tạo FamilyMember + FamilyRelationship records.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'gedcom' => ['required', 'file', 'max:10240', 'mimes:ged,txt'],
        ]);

        $content  = file_get_contents($request->file('gedcom')->getRealPath());
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        ['individuals' => $inds, 'families' => $fams] = $this->parseGedcom($content);

        if (empty($inds)) {
            return response()->json(['error' => 'Không tìm thấy dữ liệu.'], 422);
        }

        $group   = active_group();
        $userId  = Auth::id();
        $idMap   = [];  // GEDCOM ID → DB ID

        DB::transaction(function () use ($inds, $fams, $group, $userId, &$idMap) {
            // ── Tạo FamilyMember ────────────────────────────────────────
            foreach ($inds as $gedId => $ind) {
                if (empty($ind['name'])) continue;

                $member = $group->familyMembers()->create([
                    'user_id'        => $userId,
                    'name'           => $ind['name'],
                    'pronoun'        => $ind['pronoun'] ?? null,
                    'gender'         => $ind['sex'] ?? 'male',
                    'birth_year'     => $ind['birth_year'] ?? null,
                    'death_year'     => $ind['death_year'] ?? null,
                    'is_alive'       => ! ($ind['death'] ?? false),
                    'death_date_type'=> 'solar',
                    'notes'          => $ind['notes'] ?? null,
                ]);

                $idMap[$gedId] = $member->id;
            }

            $now = now();

            // ── Tạo relationships từ FAM records ─────────────────────────
            foreach ($fams as $fam) {
                $husbDbId = isset($fam['husb']) ? ($idMap[$fam['husb']] ?? null) : null;
                $wifeDbId = isset($fam['wife']) ? ($idMap[$fam['wife']] ?? null) : null;

                // Spouse
                if ($husbDbId && $wifeDbId) {
                    DB::table('family_relationships')->insertOrIgnore([
                        'member_id'         => min($husbDbId, $wifeDbId),
                        'related_member_id' => max($husbDbId, $wifeDbId),
                        'type'              => 'spouse',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }

                // Parent → child
                foreach ($fam['children'] as $childGedId) {
                    $childDbId = $idMap[$childGedId] ?? null;
                    if (! $childDbId) continue;

                    foreach (array_filter([$husbDbId, $wifeDbId]) as $parentDbId) {
                        DB::table('family_relationships')->insertOrIgnore([
                            'member_id'         => $parentDbId,
                            'related_member_id' => $childDbId,
                            'type'              => 'parent_child',
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ]);
                    }
                }
            }
        });

        $created = count($idMap);

        return response()->json([
            'created'  => $created,
            'redirect' => route('genealogy.index'),
            'message'  => "Đã import {$created} thành viên vào gia phả.",
        ]);
    }

    // ── GEDCOM Parser ─────────────────────────────────────────────────────────

    private function parseGedcom(string $content): array
    {
        $individuals = [];
        $families    = [];
        $currentType = null;
        $currentId   = null;
        $currentTag  = null; // Level-1 tag hiện tại (để capture level-2 DATE)

        foreach (preg_split('/\r?\n/', $content) as $raw) {
            $line = trim($raw);
            if (! $line) continue;

            // Format: LEVEL [@ID@] TAG [VALUE]
            if (! preg_match('/^(\d+)\s+(@[^@]+@\s+)?(\w+)(.*)?$/', $line, $m)) continue;

            $level = (int) $m[1];
            $id    = trim($m[2] ?? '');          // "@I1@" or ""
            $tag   = strtoupper(trim($m[3]));
            $value = trim($m[4] ?? '');

            // ── Level 0: record header ──────────────────────────────────
            if ($level === 0) {
                $currentTag = null;
                if ($tag === 'INDI' && $id) {
                    $currentType = 'INDI';
                    $currentId   = $id;
                    $individuals[$id] = [
                        'name' => '', 'sex' => 'male',
                        'birth_year' => null, 'death_year' => null,
                        'death' => false, 'pronoun' => null, 'notes' => null,
                    ];
                } elseif ($tag === 'FAM' && $id) {
                    $currentType = 'FAM';
                    $currentId   = $id;
                    $families[$id] = ['husb' => null, 'wife' => null, 'children' => []];
                } else {
                    $currentType = null;
                    $currentId   = null;
                }
                continue;
            }

            if (! $currentId) continue;

            // ── Level 1 ─────────────────────────────────────────────────
            if ($level === 1) {
                $currentTag = $tag;

                if ($currentType === 'INDI') {
                    switch ($tag) {
                        case 'NAME':
                            // "/surname/" format → "First /Last/" → "First Last"
                            $name = preg_replace('/\/([^\/]*)\//', '$1', $value);
                            $name = preg_replace('/\s+/', ' ', trim($name));
                            $individuals[$currentId]['name'] = $name;
                            break;
                        case 'SEX':
                            $individuals[$currentId]['sex'] = strtoupper($value) === 'F' ? 'female' : 'male';
                            break;
                        case 'DEAT':
                            $individuals[$currentId]['death'] = true;
                            break;
                        case 'TITL':
                            $individuals[$currentId]['pronoun'] = $value;
                            break;
                        case 'NOTE':
                            $individuals[$currentId]['notes'] = mb_substr($value, 0, 500);
                            break;
                    }
                } elseif ($currentType === 'FAM') {
                    switch ($tag) {
                        case 'HUSB': $families[$currentId]['husb'] = $value; break;
                        case 'WIFE': $families[$currentId]['wife'] = $value; break;
                        case 'CHIL': $families[$currentId]['children'][] = $value; break;
                    }
                }
                continue;
            }

            // ── Level 2 ─────────────────────────────────────────────────
            if ($level === 2 && $currentType === 'INDI' && $tag === 'DATE') {
                $year = $this->extractYear($value);
                if ($currentTag === 'BIRT' && $year) {
                    $individuals[$currentId]['birth_year'] = $year;
                } elseif ($currentTag === 'DEAT' && $year) {
                    $individuals[$currentId]['death_year'] = $year;
                    $individuals[$currentId]['death'] = true;
                }
            }
        }

        // Lọc bỏ records rỗng
        $individuals = array_filter($individuals, fn ($i) => ! empty($i['name']));

        return ['individuals' => $individuals, 'families' => $families];
    }

    /** Extract năm từ GEDCOM date string: "1 JAN 2000", "ABT 1950", "2000", ... */
    private function extractYear(string $date): ?int
    {
        if (preg_match('/\b(\d{4})\b/', $date, $m)) {
            $y = (int) $m[1];
            return ($y >= 1000 && $y <= 2100) ? $y : null;
        }
        return null;
    }
}
