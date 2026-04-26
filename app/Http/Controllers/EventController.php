<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemorialEventRequest;
use App\Http\Requests\UpdateMemorialEventRequest;
use App\Models\MemorialEvent;
use App\Models\Recipient;
use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $events = Auth::user()
            ->memorialEvents()
            ->orderBy('solar_date_next')
            ->get()
            ->map(fn ($e) => $this->withDaysUntil($e));

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        $this->authorizeLimit();
        $recipients = Auth::user()->recipients()->active()->orderBy('name')->get();
        $attachedRecipientIds = [];
        return view('events.create', compact('recipients', 'attachedRecipientIds'));
    }

    public function store(StoreMemorialEventRequest $request): RedirectResponse
    {
        $this->authorizeLimit();

        $validated = $request->validated();
        $recipientIds = $validated['recipient_ids'] ?? [];
        unset($validated['recipient_ids']);

        $event = Auth::user()->memorialEvents()->create([
            ...$validated,
            'solar_date_next' => $this->resolveSolarNext($request->date_type, $request->lunar_day, $request->lunar_month),
        ]);

        if (!empty($recipientIds)) {
            $event->recipients()->attach($recipientIds);
        }

        return redirect()->route('events.index')
            ->with('success', 'Đã thêm ngày giỗ ' . $request->name . '.');
    }

    public function show(MemorialEvent $event): View
    {
        $this->authorizeOwner($event);
        $event = $this->withDaysUntil($event);

        $attached   = $event->recipients()->orderBy('name')->get();
        $attachedIds = $attached->pluck('id');
        $available  = Auth::user()->recipients()
            ->whereNotIn('id', $attachedIds)
            ->orderBy('name')
            ->get();

        return view('events.show', compact('event', 'attached', 'available'));
    }

    public function attachRecipient(Request $request, MemorialEvent $event, Recipient $recipient): RedirectResponse
    {
        $this->authorizeOwner($event);
        abort_if($recipient->user_id !== Auth::id(), 403);

        $days = $request->input('notify_days_before');

        $event->recipients()->syncWithoutDetaching([
            $recipient->id => ['notify_days_before' => $days ? json_encode($days) : null],
        ]);

        return back()->with('success', 'Đã thêm ' . $recipient->name . ' vào danh sách nhận thông báo.');
    }

    public function detachRecipient(MemorialEvent $event, Recipient $recipient): RedirectResponse
    {
        $this->authorizeOwner($event);
        $event->recipients()->detach($recipient->id);

        return back()->with('success', 'Đã xóa ' . $recipient->name . ' khỏi danh sách thông báo.');
    }

    public function edit(MemorialEvent $event): View
    {
        $this->authorizeOwner($event);
        $recipients = Auth::user()->recipients()->active()->orderBy('name')->get();
        $attachedRecipientIds = $event->recipients()->pluck('recipients.id')->toArray();
        return view('events.edit', compact('event', 'recipients', 'attachedRecipientIds'));
    }

    public function update(UpdateMemorialEventRequest $request, MemorialEvent $event): RedirectResponse
    {
        $validated = $request->validated();
        $newIds = $validated['recipient_ids'] ?? [];
        unset($validated['recipient_ids']);

        $event->update([
            ...$validated,
            'solar_date_next' => $this->resolveSolarNext($request->date_type, $request->lunar_day, $request->lunar_month),
        ]);

        $currentIds = $event->recipients()->pluck('recipients.id')->toArray();
        $toDetach = array_diff($currentIds, $newIds);
        $toAttach = array_diff($newIds, $currentIds);

        if (!empty($toDetach)) {
            $event->recipients()->detach($toDetach);
        }
        if (!empty($toAttach)) {
            $event->recipients()->attach($toAttach);
        }

        return redirect()->route('events.index')
            ->with('success', 'Đã cập nhật ngày giỗ ' . $event->name . '.');
    }

    public function destroy(MemorialEvent $event): RedirectResponse
    {
        $this->authorizeOwner($event);

        $name = $event->name;
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
                ->with('error', 'Bạn đã đạt giới hạn ngày giỗ của gói hiện tại. Nâng cấp để thêm không giới hạn.'));
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
