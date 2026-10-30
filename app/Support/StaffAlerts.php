<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Listing;
use App\Models\Message;
use App\Models\SellerApplication;
use App\Models\SupportCase;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * The events an admin is alerted to, and where each one leads.
 *
 * Called from the actions that cause them — registering, sending a
 * message, raising a case, publishing into the approval queue — so every
 * path is covered, the same discipline as ListingObserver. Each alert
 * also tells open staff pages to re-read the area it belongs to.
 */
final class StaffAlerts
{
    public static function applicationSubmitted(SellerApplication $application): void
    {
        self::send(
            'alert.application',
            __('New seller application'),
            __(':business applied to sell (:reference).', [
                'business' => $application->business_name,
                'reference' => $application->reference,
            ]),
            route('admin.applications.show', $application),
            'file',
            StaffBoard::APPLICATIONS,
        );
    }

    public static function messageFlagged(Message $message): void
    {
        self::send(
            'alert.flag',
            __('A message was flagged'),
            Str::limit(Str::squish((string) $message->body), 120),
            route('admin.moderation'),
            'eye',
            StaffBoard::MODERATION,
        );
    }

    public static function caseRaised(SupportCase $case): void
    {
        self::send(
            'alert.case',
            __(':kind :reference opened', ['kind' => $case->kindLabel(), 'reference' => $case->reference]),
            Str::limit(Str::squish((string) $case->body), 120),
            route('admin.cases.show', $case),
            'shield',
            StaffBoard::CASES,
        );
    }

    public static function approvalRequested(Listing $listing): void
    {
        self::send(
            'alert.approval',
            __('A listing is waiting for approval'),
            __('“:listing” from :seller.', ['listing' => $listing->title, 'seller' => $listing->seller?->name]),
            route('admin.approvals'),
            'check',
            StaffBoard::APPROVALS,
        );
    }

    public static function featureRequested(Listing $listing): void
    {
        self::send(
            'alert.feature',
            __('A feature slot was requested'),
            __('“:listing” from :seller.', ['listing' => $listing->title, 'seller' => $listing->seller?->name]),
            route('admin.features'),
            'spark',
            StaffBoard::FEATURES,
        );
    }

    private static function send(string $type, string $title, string $body, string $url, string $icon, string $area): void
    {
        $admins = Admin::query()->where('is_active', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new StaffAlert($type, $title, $body, $url, $icon));
        }

        StaffBoard::changed($area);
    }
}
