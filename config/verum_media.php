<?php

/*
|--------------------------------------------------------------------------
| Listing media
|--------------------------------------------------------------------------
|
| Files live on the configured disk (S3 in production); only the reference
| is kept in the database, by Media Library's `media` table.
|
| Set MEDIA_DISK=s3 once the bucket credentials are in place — no code
| changes, because every upload path reads this value.
|
*/

return [

    'disk' => env('MEDIA_DISK', 'public'),

    'images' => [
        'collection' => 'images',
        'max_kb' => (int) env('MEDIA_IMAGE_MAX_KB', 5120),
        'max_files' => (int) env('MEDIA_IMAGE_MAX', 8),
        // Formats every browser renders. HEIC is deliberately excluded:
        // Chrome and Firefox cannot display it.
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'videos' => [
        'collection' => 'videos',
        'max_kb' => (int) env('MEDIA_VIDEO_MAX_KB', 30720),
        'max_files' => (int) env('MEDIA_VIDEO_MAX', 2),
        // MP4/H.264 plays everywhere; WebM covers the rest. MOV is accepted
        // because phones produce it, though older browsers may not play it.
        'mimes' => ['video/mp4', 'video/webm', 'video/quicktime'],
        'extensions' => ['mp4', 'webm', 'mov'],
    ],

    /*
    | A seller's own branding. These load on every storefront view, so the
    | caps are tighter than a listing photograph's.
    */
    'avatar' => [
        'collection' => 'avatar',
        'max_kb' => (int) env('MEDIA_AVATAR_MAX_KB', 10240),
        'max_files' => 1,
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'banner' => [
        'collection' => 'banner',
        'max_kb' => (int) env('MEDIA_BANNER_MAX_KB', 10240),
        'max_files' => 1,
        'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];
