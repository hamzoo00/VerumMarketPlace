<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One operational switch, as stored.
 *
 * Read and written through App\Support\PlatformSettings, never directly:
 * the casting and the defaults live there, and a raw read would get a
 * string where the caller expects a bool.
 */
class PlatformSetting extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<Admin, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
