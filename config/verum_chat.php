<?php

/*
|--------------------------------------------------------------------------
| In-app messaging
|--------------------------------------------------------------------------
|
| Buyers and sellers talk here, and an admin can join any conversation.
| Keeping the exchange on the platform is the point: once a deal moves to
| email or WhatsApp there is no record, no dispute evidence and no fee.
|
| Moderation is deliberately a *flag*, not a block. eBay's blunt filter
| famously refused a legitimate Best Offer because it read a price as a
| phone number — a false positive that stops a real sale costs more than
| a missed one that an admin reviews later.
|
*/

return [

    'attachments' => [
        // Images only for now; a document or archive is a malware vector
        // we are not equipped to scan.
        'max_kb' => (int) env('CHAT_IMAGE_MAX_KB', 5120),
        'max_per_message' => 3,
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'collection' => 'chat',
    ],

    'limits' => [
        'body_max' => 4000,
        // Messages per minute, per sender. Generous for a real
        // conversation, mean for a script.
        'per_minute' => 20,
    ],

    /*
    | Moderation
    |
    | Each pattern carries a weight. A message's score decides what
    | happens: nothing, a warning shown to the sender, or a flag that
    | reaches the admin queue. The message is always delivered — the
    | record of the attempt is what matters.
    */
    'moderation' => [
        'warn_at' => 2,
        'flag_at' => 3,

        'rules' => [
            'email' => [
                'label' => 'An email address',
                'weight' => 3,
                // Catches obfuscation: "name (at) host dot com".
                'pattern' => '/[\w.+-]+\s*(?:@|\(at\)|\[at\]|\s+at\s+)\s*[\w-]+\s*(?:\.|\(dot\)|\[dot\]|\s+dot\s+)\s*[a-z]{2,}/i',
            ],

            'phone' => [
                'label' => 'A phone number',
                'weight' => 3,
                /*
                 * Deliberately narrow. A bare "1500" is a price, and a
                 * certificate reference like VR-2026-002448 is on every
                 * other listing here — neither may be read as a number
                 * to ring.
                 *
                 * So: either a leading + and 9+ digits, or 9+ digits
                 * grouped by spaces/brackets. Hyphenated groups are
                 * excluded because that is what report numbers look
                 * like, and a real number is rarely written that way
                 * without a dialling code.
                 */
                'pattern' => '/(?:\+\d[\d\s().-]{8,}\d)|(?:(?<![\w-])\d{3,}[\s()]+\d{3,}[\s()]*\d{2,}(?![\w-]))/',
            ],

            'messaging_app' => [
                'label' => 'A messaging app',
                'weight' => 3,
                'pattern' => '/\b(?:whats\s*app|wechat|telegram|signal|viber|kakao|line\s+id|imo)\b/i',
            ],

            'social' => [
                'label' => 'A social handle or profile',
                'weight' => 2,
                'pattern' => '/\b(?:instagram|facebook|fb\.com|twitter|tiktok|snapchat|linkedin)\b|(?<!\w)@[A-Za-z][\w.]{2,}/i',
            ],

            'external_link' => [
                'label' => 'A link to another site',
                'weight' => 2,
                'pattern' => '/\b(?:https?:\/\/|www\.)\S+|\b[\w-]+\.(?:com|net|org|io|co|shop|store)\b/i',
            ],

            'off_platform_payment' => [
                'label' => 'Paying outside :app',
                'weight' => 3,
                'pattern' => '/\b(?:paypal|western\s*union|moneygram|bank\s*transfer|wire\s*transfer|crypto|bitcoin|usdt|iban|swift\s*code)\b/i',
            ],

            'solicitation' => [
                'label' => 'Taking the deal off :app',
                'weight' => 3,
                'pattern' => '/\b(?:off\s*(?:the\s*)?(?:platform|site)|outside\s+(?:the\s+)?(?:platform|site|app)|deal\s+direct|直接|contact\s+me\s+(?:on|at|via))\b/i',
            ],
        ],
    ],

];
