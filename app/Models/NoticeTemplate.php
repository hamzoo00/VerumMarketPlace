<?php

namespace App\Models;

use Database\Factories\NoticeTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An admin's wording for one kind of standard message, replacing the
 * default from config/verum_notices.php until it is reset.
 *
 * Read through App\Support\NoticeTemplates, never directly, so the cache
 * and the fallback to the default stay in one place.
 *
 * @property Carbon|null $updated_at
 */
class NoticeTemplate extends Model
{
    /** @use HasFactory<NoticeTemplateFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<Admin, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
