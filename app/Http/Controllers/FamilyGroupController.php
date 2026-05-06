<?php

namespace App\Http\Controllers;

use App\Models\FamilyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FamilyGroupController extends Controller
{
    /** Switch active group — lưu vào session rồi redirect về trang hiện tại */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['group_id' => ['required', 'integer']]);

        $group = Auth::user()->familyGroups()->findOrFail($request->group_id);

        session(['active_family_group_id' => $group->id]);

        return back()->with('success', 'Đã chuyển sang "' . $group->name . '".');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $group = Auth::user()->familyGroups()->create([
            'name'       => $request->name,
            'color'      => $request->color ?? '#c026d3',
            'is_default' => false,
        ]);

        session(['active_family_group_id' => $group->id]);

        return back()->with('success', 'Đã tạo "' . $group->name . '" và chuyển sang nhóm mới.');
    }

    public function update(Request $request, FamilyGroup $familyGroup): RedirectResponse
    {
        abort_if($familyGroup->user_id !== Auth::id(), 403);

        $request->validate([
            'name'  => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $familyGroup->update([
            'name'  => $request->name,
            'color' => $request->color ?? $familyGroup->color,
        ]);

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(FamilyGroup $familyGroup): RedirectResponse
    {
        abort_if($familyGroup->user_id !== Auth::id(), 403);

        $user = Auth::user();

        if ($user->familyGroups()->count() <= 1) {
            return back()->with('error', 'Không thể xóa nhóm duy nhất.');
        }

        $familyGroup->delete();

        // Switch về nhóm còn lại
        $remaining = $user->familyGroups()->first();
        session(['active_family_group_id' => $remaining->id]);

        return back()->with('success', 'Đã xóa nhóm.');
    }

    /** Toggle nhắc Rằm hoặc Mùng 1 cho group đang active */
    public function toggleReminder(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (Gate::denies('use-lunar-special-days')) {
            return back()->with('error', 'Tính năng nhắc Rằm/Mùng 1 chỉ dành cho gói Premium.');
        }

        $request->validate(['field' => ['required', 'in:remind_full_moon,remind_first_day']]);

        $group = active_group();
        $field = $request->input('field');
        $group->update([$field => ! $group->$field]);

        $labels = ['remind_full_moon' => 'Rằm', 'remind_first_day' => 'Mùng 1'];
        $state  = $group->$field ? 'bật' : 'tắt';

        return back()->with('success', "Đã {$state} nhắc {$labels[$field]}.");
    }
}

