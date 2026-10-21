<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Support\MessageOffences;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Flagged messages, awaiting a verdict.
 *
 * The queue exists because moderation flags rather than blocks: every
 * suspected attempt to take a deal off the platform lands here with its
 * thread intact, so an admin judges the exchange and not one sentence.
 */
class AdminModerationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'state' => ['nullable', 'in:pending,cleared,upheld,all'],
        ]);

        $state = $validated['state'] ?? 'pending';

        $messages = Message::query()
            ->where('is_flagged', true)
            ->with(['conversation.buyer', 'conversation.seller', 'reviewer'])
            ->when($state === 'pending', fn ($query) => $query->whereNull('review_state'))
            ->when(
                in_array($state, ['cleared', 'upheld'], true),
                fn ($query) => $query->where('review_state', $state),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.moderation', [
            'messages' => $messages,
            'state' => $state,
            'pendingCount' => Message::query()->awaitingReview()->count(),
            // One grouped query for the page: a row has to say whether
            // this sender has been here before.
            'offences' => MessageOffences::forMessages($messages->getCollection()),
        ]);
    }

    /**
     * Record a verdict.
     *
     * `cleared` is a false positive — worth recording, because a rule
     * that clears often is a rule that needs narrowing.
     */
    public function review(Request $request, Message $message): RedirectResponse
    {
        $validated = $request->validate([
            'verdict' => ['required', Rule::in([Message::REVIEW_CLEARED, Message::REVIEW_UPHELD])],
        ]);

        $message->update([
            'review_state' => $validated['verdict'],
            'reviewed_by' => $request->user('admin')?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', $validated['verdict'] === Message::REVIEW_UPHELD
            ? __('Flag upheld.')
            : __('Marked as a false positive.'));
    }
}
