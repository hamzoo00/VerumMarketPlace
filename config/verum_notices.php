<?php

/*
|--------------------------------------------------------------------------
| What staff can tell an account, and the words used by default
|--------------------------------------------------------------------------
|
| Every admin action that reaches a seller or a buyer is one of these
| kinds. The admin may write their own message; when they leave the box
| empty, the `body` here is sent instead. Either way the text actually
| sent is stored in `account_notices`, so changing a default here never
| rewrites what someone was told.
|
| These are the defaults and always the fallback. An admin may reword any
| of them from Settings (stored in `notice_templates`, read through
| App\Support\NoticeTemplates) and reset it back to the wording here.
| The placeholders a kind accepts are the ones its default uses, so a
| new placeholder starts here.
|
| Placeholders (:app is always available):
|   :listing   the listing's title          :date      a date, "12 Nov 2026"
|   :order     an order reference           :reference a certificate reference
|   :counterpart the other party's name     :excerpt   the start of a message
|
*/

return [

    'categories' => [
        'accounts' => ['label' => 'Sellers & buyers', 'icon' => 'user'],
        'moderation' => ['label' => 'Moderation', 'icon' => 'eye'],
        'listings' => ['label' => 'Listings', 'icon' => 'gem'],
        'premium' => ['label' => 'Premium', 'icon' => 'badge'],
        'orders' => ['label' => 'Orders', 'icon' => 'box'],
    ],

    'kinds' => [

        /*
        |----------------------------------------------------------------
        | Sellers & buyers
        |----------------------------------------------------------------
        */

        'warning' => [
            'category' => 'accounts',
            'label' => 'Warning',
            'to' => 'The seller or buyer',
            'counts_as_warning' => true,
            'subject' => 'A warning about your :app account',
            'action' => 'Open your account',
            'body' => 'We have reviewed activity on your account and are issuing a formal warning. Please make sure everything you do on :app follows our marketplace rules. Repeated warnings can lead to your account being suspended.',
        ],

        'suspended' => [
            'category' => 'accounts',
            'label' => 'Selling suspended',
            'to' => 'The seller',
            'subject' => 'Your selling on :app is suspended',
            'action' => 'Open your dashboard',
            'body' => 'Your selling on :app has been suspended. Your storefront and listings are hidden from buyers, and no new orders can be placed while the suspension is in place. You can still sign in to complete orders already in progress and to answer your messages.',
        ],

        'reinstated' => [
            'category' => 'accounts',
            'label' => 'Selling reinstated',
            'to' => 'The seller',
            'subject' => 'Your selling on :app is back',
            'action' => 'Open your dashboard',
            'body' => 'Your seller account has been reinstated. Your storefront and listings are visible to buyers again, and you can take orders as before.',
        ],

        'closing' => [
            'category' => 'accounts',
            'label' => 'Account closing',
            'to' => 'The seller',
            'subject' => 'Your :app seller account is closing',
            'action' => 'See your open orders',
            'body' => 'Your seller account is being closed. Your storefront and listings have been taken down and no new orders can be placed. Orders that had not been dispatched have been cancelled and the buyers told. Orders already on their way stay open until :date, so they can arrive and any dispute can be settled — please complete them as normal.',
        ],

        'closed' => [
            'category' => 'accounts',
            'label' => 'Account closed',
            'to' => 'The seller',
            'subject' => 'Your :app seller account is closed',
            'action' => null,
            'body' => 'Your seller account is now closed and can no longer be used to sign in. Thank you for selling on :app.',
        ],

        // Recorded when a case is resolved as "Seller warned". The case's
        // own email already tells them, so this one is never sent.
        'case_warning' => [
            'category' => 'accounts',
            'label' => 'Warning from a case',
            'to' => 'The seller',
            'counts_as_warning' => true,
            'record_only' => true,
            'subject' => 'A warning from a case about your store',
            'action' => null,
            'body' => 'A case raised about your store was resolved with a warning.',
        ],

        /*
        |----------------------------------------------------------------
        | Moderation
        |----------------------------------------------------------------
        */

        'flag_warning' => [
            'category' => 'moderation',
            'label' => 'Flag upheld — to the sender',
            'to' => 'Whoever wrote the message',
            'counts_as_warning' => true,
            'subject' => 'A warning about a message you sent on :app',
            'action' => 'Open the conversation',
            'body' => 'Your message to :counterpart — “:excerpt” — asked to move the deal or the payment off :app. That is not allowed: it leaves you and the other person without our protection if anything goes wrong. Please keep contact details and payment on :app. This has been recorded as a warning on your account.',
        ],

        'safety_notice' => [
            'category' => 'moderation',
            'label' => 'Flag upheld — to the other party',
            'to' => 'The other person in the conversation',
            'subject' => 'Staying safe on :app',
            'action' => 'Open the conversation',
            'body' => 'We reviewed a message in one of your conversations and found it tried to move the deal off :app. For your protection, keep every payment and every conversation on :app, and never share bank details, phone numbers or other contact details. If you are asked to pay outside the platform, please tell us.',
        ],

        /*
        |----------------------------------------------------------------
        | Listings
        |----------------------------------------------------------------
        */

        'listing_approved' => [
            'category' => 'listings',
            'label' => 'Listing approved',
            'to' => 'The seller',
            'subject' => '“:listing” is approved',
            'action' => 'View the listing',
            'body' => 'Your listing “:listing” has been approved and is now live on :app.',
        ],

        'listing_rejected' => [
            'category' => 'listings',
            'label' => 'Listing not approved',
            'to' => 'The seller',
            'subject' => '“:listing” was not approved',
            'action' => 'Edit the listing',
            'body' => 'Your listing “:listing” was not approved. Please check that the photographs, the description and the gem details are accurate and complete, then edit it and submit it again.',
        ],

        'feature_granted' => [
            'category' => 'listings',
            'label' => 'Featured',
            'to' => 'The seller',
            'subject' => '“:listing” is now featured',
            'action' => 'View your inventory',
            'body' => 'Your listing “:listing” is now featured on the :app home page.',
        ],

        'feature_declined' => [
            'category' => 'listings',
            'label' => 'Feature request declined',
            'to' => 'The seller',
            'subject' => 'About featuring “:listing”',
            'action' => 'View your inventory',
            'body' => 'We could not feature “:listing” this time. You are welcome to ask again for another listing.',
        ],

        'feature_ended' => [
            'category' => 'listings',
            'label' => 'Removed from featured',
            'to' => 'The seller',
            'subject' => '“:listing” is no longer featured',
            'action' => 'View your inventory',
            'body' => '“:listing” has been taken out of the featured section and is back in the regular catalogue.',
        ],

        /*
        |----------------------------------------------------------------
        | Premium
        |----------------------------------------------------------------
        */

        'premium_granted' => [
            'category' => 'premium',
            'label' => 'Premium granted',
            'to' => 'The seller',
            'subject' => 'Your store is now premium on :app',
            'action' => 'Open your inventory',
            'body' => 'Your store now has premium standing on :app. You can ask for your listings to be featured from your inventory.',
        ],

        'premium_revoked' => [
            'category' => 'premium',
            'label' => 'Premium ended',
            'to' => 'The seller',
            'subject' => 'Your premium standing on :app has ended',
            'action' => 'Open your dashboard',
            'body' => 'Your store’s premium standing has ended. Any featured listings have returned to the regular catalogue.',
        ],

        'certified' => [
            'category' => 'premium',
            'label' => 'Certificate issued',
            'to' => 'The seller',
            'subject' => 'Your store is verified on :app',
            'action' => 'Open your storefront',
            'body' => 'We have verified your store and issued certificate :reference. It now appears on every listing you publish.',
        ],

        'decertified' => [
            'category' => 'premium',
            'label' => 'Certificate withdrawn',
            'to' => 'The seller',
            'subject' => 'Your :app verification certificate was withdrawn',
            'action' => 'Open your dashboard',
            'body' => 'Your store’s verification certificate has been withdrawn and no longer appears on your listings.',
        ],

        /*
        |----------------------------------------------------------------
        | Orders
        |----------------------------------------------------------------
        */

        'order_cancelled_closure' => [
            'category' => 'orders',
            'label' => 'Order cancelled — store closed',
            'to' => 'The buyer',
            'subject' => 'Your order :order was cancelled',
            'action' => 'View the order',
            'body' => 'We are sorry — your order :order has been cancelled because the seller has closed their store on :app. If you have already paid the seller, they must refund you in full. If they do not, open a dispute and we will step in.',
        ],
    ],

];
