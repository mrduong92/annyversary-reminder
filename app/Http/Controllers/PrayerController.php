<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrayerRequest;
use App\Models\Prayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PrayerController extends Controller
{
    public function index(): View
    {
        $myPrayers     = active_group()->prayers()->with('memorialEvent')->latest()->get();
        $systemPrayers = Prayer::where('is_system', true)->orderBy('title')->get();
        return view('prayers.index', compact('myPrayers', 'systemPrayers'));
    }

    public function show(Prayer $prayer): View
    {
        // Văn khấn hệ thống ai cũng xem được; văn khấn cá nhân chỉ chủ sở hữu xem
        abort_if(! $prayer->is_system && $prayer->user_id !== Auth::id(), 403);
        return view('prayers.show', compact('prayer'));
    }

    public function edit(Prayer $prayer): View
    {
        abort_if($prayer->is_system, 403, 'Không thể sửa văn khấn mẫu.');
        abort_if($prayer->user_id !== Auth::id(), 403);
        return view('prayers.edit', compact('prayer'));
    }

    public function update(Request $request, Prayer $prayer): RedirectResponse
    {
        abort_if($prayer->is_system, 403, 'Không thể sửa văn khấn mẫu.');
        abort_if($prayer->user_id !== Auth::id(), 403);

        $request->validate([
            'title'   => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
        ]);

        $prayer->update($request->only('title', 'content'));

        return redirect()->route('prayers.show', $prayer)
            ->with('success', 'Đã cập nhật văn khấn.');
    }

    public function destroy(Prayer $prayer): RedirectResponse
    {
        abort_if($prayer->is_system, 403, 'Không thể xóa văn khấn mẫu.');
        abort_if($prayer->user_id !== Auth::id(), 403);
        $prayer->delete();
        return redirect()->route('prayers.index')->with('success', 'Đã xóa văn khấn.');
    }

    public function storeFromChat(StorePrayerRequest $request): JsonResponse
    {
        $prayer = active_group()->prayers()->create([
            'user_id'           => Auth::id(),
            'title'             => $request->title,
            'content'           => $request->content,
            'memorial_event_id' => $request->memorial_event_id,
            'ai_generated'      => true,
        ]);

        return response()->json([
            'id'      => $prayer->id,
            'message' => 'Đã lưu văn khấn "' . $prayer->title . '".',
            'url'     => route('prayers.show', $prayer),
        ]);
    }
}
