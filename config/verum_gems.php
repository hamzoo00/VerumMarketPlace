<?php

/*
|--------------------------------------------------------------------------
| Gem details a listing may carry
|--------------------------------------------------------------------------
|
| The trade's own vocabulary, so two sellers describing the same stone use
| the same words and a buyer comparing them compares like with like.
| Read through App\Support\GemSpec; the listing form, ListingRequest and
| the browser-side validator all take their lists and patterns from here.
|
| Values are stored as written, so renaming one strands the listings that
| already use it — add rather than rename.
|
*/

return [

    'shapes' => [
        'Round', 'Oval', 'Cushion', 'Emerald cut', 'Pear', 'Marquise', 'Heart',
        'Princess', 'Radiant', 'Asscher', 'Trillion', 'Baguette', 'Octagon',
        'Square', 'Cabochon', 'Briolette', 'Freeform', 'Rough',
    ],

    // How the facets are arranged — independent of the outline above, so
    // an oval may be brilliant, step or mixed.
    'cutting_styles' => [
        'Brilliant', 'Step', 'Mixed', 'Checkerboard', 'Cabochon', 'Rose',
        'Portuguese', 'Precision', 'Carved', 'Fantasy', 'Uncut',
    ],

    /*
     * Coloured stones are graded by eye, diamonds on the GIA scale; a
     * gem marketplace sells both, so both vocabularies are offered and
     * the dropdown groups them.
     */
    'clarity' => [
        'Coloured stones' => [
            'Loupe clean', 'Eye clean', 'Very slightly included',
            'Slightly included', 'Included', 'Heavily included',
        ],
        'Diamonds' => ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2', 'I1', 'I2', 'I3'],
    ],

    'labs' => [
        'GIA', 'AGL', 'GRS', 'Gübelin', 'SSEF', 'Lotus Gemology', 'AIGS', 'GIT',
        'IGI', 'HRD', 'CGL', 'Other laboratory',
    ],

    /*
     * Shapes of free text, as regex bodies.
     *
     * Each is used twice — as `regex:` on the server and as the HTML
     * `pattern` the browser checks — so they are written in the subset
     * both engines read the same way: every punctuation mark inside a
     * class is escaped, which the browser's `v` flag requires.
     */
    'patterns' => [
        // 8.1 × 6.4 × 4.2 mm — two or three measurements, unit optional.
        'dimensions' => '\d{1,3}(?:[\.,]\d{1,2})?(?:\s*[xX×]\s*\d{1,3}(?:[\.,]\d{1,2})?){1,2}(?:\s*[mM]{2})?',

        // Words a person would write: "Pigeon-blood red". Not "2".
        'words' => '(?=.*\p{L})[\p{L}\p{M}\s\'’\-,\.\(\)\/&]{2,60}',

        // As above, with figures allowed: "Heated (H)", "Mine 2".
        'phrase' => '(?=.*\p{L})[\p{L}\p{M}\p{N}\s\'’\-,\.\(\)\/&]{2,120}',

        // A title has to say what the thing is, so it needs a letter.
        'title' => '(?=.*\p{L}).{3,120}',

        'sku' => '[A-Za-z0-9][A-Za-z0-9\-_\.\/]{0,59}',

        'report_number' => '[A-Za-z0-9][A-Za-z0-9\-\/ ]{2,39}',
    ],

    'limits' => [
        // Carats, to two places. The ceiling admits a large specimen
        // while catching a price typed into the wrong box.
        'carat_max' => 10000,
    ],

];
