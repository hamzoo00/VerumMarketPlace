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

        /*
        | References to blank out before scanning
        |
        | A gem marketplace is full of long digit runs that are evidence,
        | not contact details — `VR-2026-002448` on every listing here,
        | and a ten-digit GIA number on any stone worth certifying. Both
        | read exactly like a phone number.
        |
        | Removing them first is what lets the phone rule be strict
        | enough to catch an unbroken `03315084546`, which it missed
        | while the pattern was narrowed around these instead.
        */
        'references' => [
            // Letters joined straight to digits: VR-2026-002448,
            // GRS2024-091234. The letters must touch the number — "me
            // 0300-1234567" is a phone number, not a reference.
            '~\b[A-Za-z]{2,6}(?:-\d[\d-]*|\d[\d-]*)\b~i',

            // A named lab and its report number, with an optional word
            // between: "GIA 2185647321", "IGI #425192837",
            // "GIA report 2185647321".
            '~\b(?:GIA|IGI|AGL|GRS|SSEF|Gubelin|GUB|GIT|AIGS|Lotus|ICA|GII|HRD|CGL|EGL)\b'
                .'(?:\s+(?:report|cert(?:ificate)?|no\.?|number|#))?\s*#?\s*\d[\d\s-]*~i',
        ],

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
                 * Three shapes, scanned *after* references are stripped:
                 *
                 *   +92 331 5084546   a dialling code and 9+ digits
                 *   0331 508 4546     groups split by space/bracket/dash
                 *   03315084546       an unbroken run of 9-15 digits
                 *
                 * The last one is the common case and was missing: a
                 * local mobile typed straight out passed clean through.
                 * It is safe now only because a certificate reference is
                 * blanked before this runs — and no gem price here is
                 * nine digits.
                 */
                'pattern' => '~(?:\+\d[\d\s().-]{7,}\d)'
                    .'|(?<![\w])\d{2,}[\s().-]+\d{3,}[\s().-]*\d{2,}(?![\w])'
                    .'|(?<![\w.-])\d{9,15}(?![\w.-])~',
            ],

            'messaging_app' => [
                'label' => 'A messaging app',
                'weight' => 3,
                /*
                 * Abbreviations included ("wa", "tg") only where they
                 * sit next to a number or a handle, since on their own
                 * they are ordinary words.
                 */
                'pattern' => '/\b(?:whats\s*app|whatsap+|wsap|wechat|we\s*chat|telegram|viber|'
                    .'kakao|imo|botim|zangi|skype|discord|messenger|'
                    // "signal" and "line" are ordinary words, so they
                    // need app context: "signal me" is not a flag,
                    // "signal app" and "line id" are.
                    .'signal\s*(?:app|id|number|me\s+on)|(?:on|via|use)\s+signal\b|'
                    .'line\s*(?:id|app)|'
                    .'(?:wa|tg)\s*(?:no|number|id)\b)/i',
            ],

            'social' => [
                'label' => 'A social handle or profile',
                'weight' => 2,
                'pattern' => '/\b(?:instagram|insta\b|ig\s*(?:handle|id|page)|facebook|fb\.com|fb\s*(?:page|id)|'
                    .'twitter|tiktok|snapchat|snap\s*(?:chat|id)|linkedin|pinterest|youtube|'
                    // Rival marketplaces only where a visit is implied:
                    // "my alibaba store", not "cheaper than eBay".
                    .'(?:alibaba|aliexpress|ebay|etsy|amazon)\s*(?:store|shop|page|listing|profile|link))\b'
                    .'|(?<!\w)@[A-Za-z][\w.]{2,}/i',
            ],

            'external_link' => [
                'label' => 'A link to another site',
                'weight' => 2,
                'pattern' => '/\b(?:https?:\/\/|www\.)\S+|\b[\w-]+\.(?:com|net|org|io|co|shop|store)\b/i',
            ],

            'off_platform_payment' => [
                'label' => 'Paying outside :app',
                'weight' => 3,
                /*
                 * Named rails rather than the word "payment", because a
                 * buyer asking "when is payment due" is not a problem.
                 *
                 * An irreversible rail is the point: a wire or a crypto
                 * transfer cannot be clawed back once it lands, which is
                 * exactly why a scammer asks for one, and why consumer
                 * regulators single them out.
                 */
                'pattern' => '/\b(?:paypal|venmo|cash\s*app|zelle|revolut|wise(?:\s*transfer)?|payoneer|'
                    .'western\s*union|moneygram|ria\s*money|remitly|'
                    .'bank\s*(?:transfer|details|account)|wire\s*(?:transfer|me|payment)|'
                    .'crypto|bitcoin|btc|ethereum|eth|usdt|tether|binance|'
                    .'iban|swift\s*code|routing\s*number|sort\s*code|'
                    .'hawala|hundi|easypaisa|jazzcash)\b/i',
            ],

            'fee_avoidance' => [
                'label' => 'Avoiding :app fees',
                'weight' => 3,
                /*
                 * The explicit pitch. Etsy bans this outright, and it is
                 * the most honest signal there is: nobody says "save the
                 * fee" by accident, so a match here is rarely innocent.
                 */
                'pattern' => '/\b(?:(?:avoid|skip|save|dodge|without|no)\s+(?:the\s+)?(?:fee|fees|commission)'
                    .'|cheaper\s+(?:if|when|direct)'
                    .'|(?:no|less)\s+commission'
                    .'|cut\s+out\s+the\s+middle\s*man)\b/i',
            ],

            'cancel_to_relist' => [
                'label' => 'Cancelling to deal privately',
                'weight' => 3,
                /*
                 * The sale is agreed here, then one side asks to cancel
                 * and settle outside — so the record shows nothing sold
                 * and neither party is covered. Worse than a contact
                 * swap, because it undoes a real transaction.
                 */
                'pattern' => '/\b(?:cancel\s+(?:the\s+)?(?:order|sale|bid|auction)\s+(?:and|then|so)'
                    .'|(?:buy|sell|pay|settle|handle)\s+(?:it\s+)?(?:privately|direct(?:ly)?|between\s+us)'
                    .'|keep\s+(?:this|it)\s+between\s+us)\b/i',
            ],

            'authenticity_dodge' => [
                'label' => 'Trading without the certificate',
                'weight' => 2,
                /*
                 * Gem-specific, and deliberately narrow: "this stone
                 * has no certificate" is an honest disclosure every
                 * seller must be able to make without a warning, so
                 * only the *persuading* forms match — *don't* bother
                 * with the cert, *skip* the lab, trust me instead.
                 *
                 * Weight 2, because even those have innocent readings.
                 * It is a signal when it lands beside something else:
                 * an uncertified stone moved off-platform is how a
                 * synthetic sells as natural with no record of the
                 * claim.
                 */
                'pattern' => '/\b(?:no\s+need\s+for\s+(?:a\s+|the\s+)?(?:cert|certificate|paperwork|lab\s*report)'
                    .'|(?:don\x27t|dont|do\s+not)\s+(?:bother|worry)\s+(?:about|with)\s+(?:the\s+)?(?:cert|certificate|paperwork|lab)'
                    .'|skip\s+(?:the\s+)?(?:cert|certificate|lab|paperwork)'
                    .'|forget\s+(?:the\s+)?(?:cert|certificate|paperwork)'
                    .'|trust\s+me\s+(?:on\s+)?(?:it|this|the\s+stone))\b/i',
            ],

            'solicitation' => [
                'label' => 'Taking the deal off :app',
                'weight' => 3,
                'pattern' => '/\b(?:off\s*(?:the\s*)?(?:platform|site)|outside\s+(?:the\s+)?(?:platform|site|app)|deal\s+direct|直接|contact\s+me\s+(?:on|at|via))\b/i',
            ],
        ],
    ],

];
