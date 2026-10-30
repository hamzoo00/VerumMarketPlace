<?php

namespace App\Support;

use App\Models\NoticeTemplate;
use Illuminate\Support\Facades\Cache;

/**
 * Admins' own wording for the standard messages.
 *
 * The default for every kind lives in config/verum_notices.php and stays
 * the fallback: a kind an admin never edited, or one they reset, reads
 * from code. Cached forever and cleared on every write, like
 * PlatformSettings, because a notice goes out on every decision and an
 * edit must take effect at once.
 *
 * NoticeCatalogue is the only caller that resolves wording; this only
 * stores it.
 */
final class NoticeTemplates
{
    private const CACHE_KEY = 'verum:notice-templates';

    /**
     * Every edited kind.
     *
     * @return array<string, array{subject: string, body: string, editor: string|null, updated_at: string|null}>
     */
    public static function all(): array
    {
        /** @var array<string, array{subject: string, body: string, editor: string|null, updated_at: string|null}> */
        return Cache::rememberForever(self::CACHE_KEY, static fn (): array => NoticeTemplate::query()
            ->with('editor:id,username')
            ->get()
            ->filter(fn (NoticeTemplate $template): bool => NoticeCatalogue::has($template->kind))
            ->mapWithKeys(fn (NoticeTemplate $template): array => [$template->kind => [
                'subject' => (string) $template->subject,
                'body' => (string) $template->body,
                'editor' => $template->editor?->username,
                'updated_at' => $template->updated_at?->toIso8601String(),
            ]])
            ->all());
    }

    /** @return array{subject: string, body: string, editor: string|null, updated_at: string|null}|null */
    public static function for(string $kind): ?array
    {
        return self::all()[$kind] ?? null;
    }

    public static function save(string $kind, string $subject, string $body, ?int $adminId): void
    {
        NoticeTemplate::query()->updateOrCreate(
            ['kind' => $kind],
            ['subject' => $subject, 'body' => $body, 'updated_by' => $adminId],
        );

        self::flush();
    }

    /** Back to the default in code. */
    public static function reset(string $kind): void
    {
        NoticeTemplate::query()->where('kind', $kind)->delete();

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The placeholders a kind can fill: the ones its default uses, and
     * :app. Taken from the default rather than listed separately, so the
     * two cannot drift apart.
     *
     * @return list<string>
     */
    public static function placeholders(string $kind): array
    {
        $definition = NoticeCatalogue::definition($kind);

        preg_match_all('/:([a-z]+)/', $definition['subject'].' '.$definition['body'], $matches);

        return array_values(array_unique(['app', ...$matches[1]]));
    }

    /**
     * Placeholders in the text that this kind would leave unfilled — a
     * typo like ":lisitng" would otherwise go out to a seller as written.
     *
     * @return list<string>
     */
    public static function unknownPlaceholders(string $kind, string $text): array
    {
        preg_match_all('/:([a-z]+)/', $text, $matches);

        return array_values(array_diff(array_unique($matches[1]), self::placeholders($kind)));
    }
}
