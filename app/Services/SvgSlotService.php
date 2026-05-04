<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;

/**
 * Parses fixed-position member slots from Gia_Pha_3.svg
 * and assigns ordered family members into those slots.
 */
class SvgSlotService
{
    /**
     * Generation index → ordered slot IDs (left-to-right) for couple-root mode.
     * Objects 2 and 3 are skipped (designed for single-patriarch case).
     */
    private const SLOT_ROWS = [
        0 => ['Object 71', 'Object 72'],
        1 => ['Object 67', 'Object 68', 'Object 69', 'Object 70'],
        2 => ['Object 52', 'Object 53', 'Object 54', 'Object 55', 'Object 56'],
        3 => ['Object 4',  'Object 5',  'Object 6',  'Object 7',  'Object 8',
              'Object 9',  'Object 10', 'Object 11', 'Object 12', 'Object 13', 'Object 14'],
        4 => ['Object 15', 'Object 16', 'Object 17', 'Object 18', 'Object 19',
              'Object 20', 'Object 21', 'Object 22', 'Object 23', 'Object 24',
              'Object 25', 'Object 26', 'Object 27', 'Object 28', 'Object 29', 'Object 30',
              'Object 47', 'Object 48', 'Object 49', 'Object 50', 'Object 51'],
        5 => ['Object 57', 'Object 58', 'Object 59', 'Object 60', 'Object 61',
              'Object 62', 'Object 63', 'Object 64', 'Object 65', 'Object 66'],
        6 => ['Object 31', 'Object 32', 'Object 33', 'Object 34', 'Object 35', 'Object 36',
              'Object 37', 'Object 38', 'Object 39', 'Object 40', 'Object 41', 'Object 42',
              'Object 43', 'Object 44', 'Object 45', 'Object 46'],
    ];

    /**
     * Parse all named-Object paths from the SVG and return their bounding data.
     *
     * @return array<string, array{id:string, x:float, y:float, w:float, h:float, cx:float, cy:float}>
     */
    public function parseSlots(string $svgPath): array
    {
        $content = file_get_contents($svgPath);
        $slots   = [];

        for ($i = 2; $i <= 73; $i++) {
            if (!preg_match('/id="Object ' . $i . '"[^>]*d="([^"]+)"/', $content, $m)) {
                continue;
            }
            $d = $m[1];

            // Starting coordinate from first 'm' command
            if (!preg_match('/^m([\d.]+)\s+([\d.]+)/', $d, $start)) {
                continue;
            }
            $x = (float) $start[1];
            $y = (float) $start[2];

            // Width: first positive h-segment (the outer horizontal span)
            preg_match('/h([\d.]+)/', $d, $hw);
            $w = isset($hw[1]) ? (float) $hw[1] : 0;

            // For Object 2 the first h-segment is partial (curved top box) —
            // sum ALL positive h-segments to approximate full width
            if ($i === 2) {
                preg_match_all('/h([\d.]+)/', $d, $allH);
                $w = array_sum(array_map('floatval', $allH[1]));
            }

            // Height: first positive v-segment
            preg_match('/v([\d.]+)/', $d, $vh);
            $h = isset($vh[1]) ? (float) $vh[1] : 80;

            $slots["Object $i"] = [
                'id' => "Object $i",
                'x'  => $x,
                'y'  => $y,
                'w'  => $w,
                'h'  => $h,
                'cx' => $x + $w / 2,
                'cy' => $y + $h / 2,
            ];
        }

        return $slots;
    }

    /**
     * Assign an ordered list of members-per-generation to SVG slots.
     *
     * @param  array<int, FamilyMember[]> $generations  BFS result: gen_index => members[]
     * @param  array<string, array>       $slots         Output of parseSlots()
     * @return array<array{slot: array, member: FamilyMember}>
     */
    public function assign(array $generations, array $slots): array
    {
        $assignments = [];

        foreach (self::SLOT_ROWS as $genIndex => $slotIds) {
            $members = $generations[$genIndex] ?? [];
            if (empty($members)) {
                continue;
            }

            // Gen 0: male → left slot (Object 71), female → right slot (Object 72)
            if ($genIndex === 0) {
                $male   = collect($members)->first(fn ($m) => $m->gender === 'male');
                $female = collect($members)->first(fn ($m) => $m->gender === 'female');

                // Fallback: no gender info — assign by order
                if (!$male && !$female) {
                    $male   = $members[0] ?? null;
                    $female = $members[1] ?? null;
                } elseif (!$male) {
                    // Only female known — put on left
                    $male   = $female;
                    $female = null;
                }

                foreach ([[$slotIds[0] ?? null, $male], [$slotIds[1] ?? null, $female]] as [$sid, $mem]) {
                    if ($sid && $mem && isset($slots[$sid])) {
                        $assignments[] = ['slot' => $slots[$sid], 'member' => $mem];
                    }
                }
                continue;
            }

            // Gen 1+: fill left-to-right by birth_year order
            foreach ($members as $i => $member) {
                $slotId = $slotIds[$i] ?? null;
                if (!$slotId || !isset($slots[$slotId])) {
                    break; // no more slots in this row
                }
                $assignments[] = ['slot' => $slots[$slotId], 'member' => $member];
            }
        }

        return $assignments;
    }
}
