<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipientRequest;
use App\Http\Requests\UpdateRecipientRequest;
use App\Models\Recipient;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscription) {}

    public function index(): View
    {
        $recipients = active_group()
            ->recipients()
            ->withCount('memorialEvents')
            ->orderBy('name')
            ->get();

        return view('recipients.index', compact('recipients'));
    }

    public function create(): View
    {
        $this->authorizeLimit();
        return view('recipients.create');
    }

    public function store(StoreRecipientRequest $request): RedirectResponse
    {
        $this->authorizeLimit();

        active_group()->recipients()->create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('recipients.index')
            ->with('success', 'Đã thêm người nhận ' . $request->name . '.');
    }

    public function edit(Recipient $recipient): View
    {
        $this->authorizeOwner($recipient);

        $linkedEventIds = $recipient->memorialEvents()->pluck('memorial_events.id');
        $allEvents      = active_group()->memorialEvents()->orderBy('name')->get();

        return view('recipients.edit', compact('recipient', 'linkedEventIds', 'allEvents'));
    }

    public function update(UpdateRecipientRequest $request, Recipient $recipient): RedirectResponse
    {
        $recipient->update($request->validated());

        return redirect()->route('recipients.index')
            ->with('success', 'Đã cập nhật ' . $recipient->name . '.');
    }

    public function destroy(Recipient $recipient): RedirectResponse
    {
        $this->authorizeOwner($recipient);
        $name = $recipient->name;
        $recipient->delete();

        return redirect()->route('recipients.index')
            ->with('success', 'Đã xóa người nhận ' . $name . '.');
    }

    private function authorizeOwner(Recipient $recipient): void
    {
        abort_if($recipient->user_id !== Auth::id(), 403);
    }

    private function authorizeLimit(): void
    {
        if (! $this->subscription->canAddRecipient(Auth::user())) {
            abort(redirect()->route('recipients.index')
                ->with('error', 'Bạn đã đạt giới hạn người nhận. Nâng cấp để thêm không giới hạn.'));
        }
    }
}
