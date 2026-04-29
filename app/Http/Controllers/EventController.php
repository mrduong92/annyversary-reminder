<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemorialEventRequest;
use App\Http\Requests\UpdateMemorialEventRequest;
use App\Models\MemorialEvent;
use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService $subscription,
    ) {}

    public function index(): View
    {
        $events = active_group()
            ->memorialEvents()
            ->with('familyMember')
            ->orderBy('solar_date_next')
            ->get()
            ->map(fn ($e) => $this->withDaysUntil($e));

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        $this->authorizeLimit();

        $existingMemberIds = active_group()->memorialEvents()->pluck('family_member_id');
        $availableMembers  = active_group()->familyMembers()
            ->whereNotNull('death_year') // Chỉ hiển thị thành viên đã mất
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('death_year', 'desc')
            ->orderBy('name')
            ->get();
        $allMembers = active_group()->familyMembers()
            ->whereNotNull('death_year') // Chỉ hiển thị thành viên đã mất
            ->orderBy('name')
            ->get();

        return view('events.create', compact('availableMembers', 'allMembers'));
    }

    public function store(StoreMemorialEventRequest $request): RedirectResponse
    {
        $this->authorizeLimit();

        $validated = $request->validated();
        $event = active_group()->memorialEvents()->create([
            ...$validated,
            'user_id'         => Auth::id(),
            'solar_date_next' => $this->resolveSolarNext($request->date_type, $request->lunar_day, $request->lunar_month),
        ]);

        // Sync ngược: cập nhật family_member.memorial_event_id
        \App\Models\FamilyMember::where('id', $validated['family_member_id'])
            ->whereNull('memorial_event_id')
            ->update(['memorial_event_id' => $event->id]);

        return redirect()->route('events.index')
            ->with('success', 'Đã thêm ngày giỗ cho ' . $event->displayName() . '.');
    }

    public function show(MemorialEvent $event): View
    {
        $this->authorizeOwner($event);
        $event = $this->withDaysUntil($event);
        return view('events.show', compact('event'));
    }

    public function edit(MemorialEvent $event): View
    {
        $this->authorizeOwner($event);
        $allMembers = active_group()->familyMembers()
            ->whereNotNull('death_year') // Chỉ hiển thị thành viên đã mất
            ->orderBy('name')
            ->get();
        return view('events.edit', compact('event', 'allMembers'));
    }

    public function update(UpdateMemorialEventRequest $request, MemorialEvent $event): RedirectResponse
    {
        $validated = $request->validated();

        $event->update([
            ...$validated,
            'solar_date_next' => $this->resolveSolarNext($request->date_type, $request->lunar_day, $request->lunar_month),
        ]);

        return redirect()->route('events.index')
            ->with('success', 'Đã cập nhật ngày giỗ ' . $event->displayName() . '.');
    }

    public function destroy(MemorialEvent $event): RedirectResponse
    {
        $this->authorizeOwner($event);
        $name = $event->displayName();
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', 'Đã xóa ngày giỗ ' . $name . '.');
    }

    private function resolveSolarNext(string $dateType, int $day, int $month): \Carbon\Carbon
    {
        return $dateType === 'solar'
            ? $this->lunar->nextSolarOccurrence($day, $month)
            : $this->lunar->nextOccurrence($day, $month);
    }

    private function authorizeOwner(MemorialEvent $event): void
    {
        abort_if($event->user_id !== Auth::id(), 403);
    }

    private function authorizeLimit(): void
    {
        if (! $this->subscription->canAddEvent(Auth::user())) {
            abort(redirect()->route('events.index')
                ->with('error', 'Bạn đã đạt giới hạn ngày giỗ. Nâng cấp để thêm không giới hạn.'));
        }
    }

    private function withDaysUntil(MemorialEvent $event): MemorialEvent
    {
        $event->days_until = $event->solar_date_next
            ? $this->lunar->daysUntil($event->solar_date_next)
            : null;
        return $event;
    }
}
