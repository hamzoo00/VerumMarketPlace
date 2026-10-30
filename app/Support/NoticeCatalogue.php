<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * The kinds of notice staff can send, read from config/verum_notices.php.
 *
 * The only place a default's wording is resolved, so the email, the
 * in-app notification, the admin's preview in the message dialog and the
 * Settings page all show the same sentence.
 */
final class NoticeCatalogue
{
    /** @return array<string, array{label: string, icon: string}> */
    public static function categories(): array
    {
        $categories = [];

        foreach (self::config('verum_notices.categories') as $key => $category) {
            $categories[$key] = ['label' => (string) __($category['label']), 'icon' => (string) $category['icon']];
        }

        return $categories;
    }

    /**
     * Every kind, or those in one category, keyed by kind.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function kinds(?string $category = null): array
    {
        return array_filter(
            self::config('verum_notices.kinds'),
            fn (array $definition): bool => $category === null || $definition['category'] === $category,
        );
    }

    public static function has(string $kind): bool
    {
        return config("verum_notices.kinds.{$kind}") !== null;
    }

    /** @return array<string, mixed> */
    public static function definition(string $kind): array
    {
        $definition = config("verum_notices.kinds.{$kind}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown notice kind [{$kind}].");
        }

        return $definition;
    }

    /**
     * The subject line, in an admin's wording if they edited it.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function subject(string $kind, array $replace = []): string
    {
        $edited = NoticeTemplates::for($kind);

        return $edited === null
            ? __(self::definition($kind)['subject'], self::withApp($replace))
            : self::fill($edited['subject'], $replace);
    }

    /**
     * The words sent when the admin acting writes none — the edited
     * wording if Settings has one, otherwise the default in code.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function defaultBody(string $kind, array $replace = []): string
    {
        $edited = NoticeTemplates::for($kind);

        return $edited === null
            ? __(self::definition($kind)['body'], self::withApp($replace))
            : self::fill($edited['body'], $replace);
    }

    /** The subject as it stands, placeholders and all — what the edit form shows. */
    public static function subjectTemplate(string $kind): string
    {
        return NoticeTemplates::for($kind)['subject'] ?? (string) __(self::definition($kind)['subject']);
    }

    /** The body as it stands, placeholders and all. */
    public static function bodyTemplate(string $kind): string
    {
        return NoticeTemplates::for($kind)['body'] ?? (string) __(self::definition($kind)['body']);
    }

    /** The wording in code, which a reset returns to. */
    public static function originalBody(string $kind): string
    {
        return (string) __(self::definition($kind)['body']);
    }

    public static function isEdited(string $kind): bool
    {
        return NoticeTemplates::for($kind) !== null;
    }

    public static function label(string $kind): string
    {
        return __(self::definition($kind)['label']);
    }

    public static function actionLabel(string $kind): ?string
    {
        $action = self::definition($kind)['action'] ?? null;

        return $action === null ? null : (string) __($action);
    }

    public static function category(string $kind): string
    {
        return (string) self::definition($kind)['category'];
    }

    public static function icon(string $kind): string
    {
        return (string) (config('verum_notices.categories.'.self::category($kind).'.icon') ?? 'info');
    }

    public static function countsAsWarning(string $kind): bool
    {
        return (bool) (self::definition($kind)['counts_as_warning'] ?? false);
    }

    /** Recorded for the history, but delivered by something else. */
    public static function isRecordOnly(string $kind): bool
    {
        return (bool) (self::definition($kind)['record_only'] ?? false);
    }

    /** @return list<string> */
    public static function warningKinds(): array
    {
        $kinds = array_filter(
            self::config('verum_notices.kinds'),
            fn (array $definition): bool => (bool) ($definition['counts_as_warning'] ?? false),
        );

        return array_map('strval', array_keys($kinds));
    }

    /** @return array<string, array<string, mixed>> */
    private static function config(string $key): array
    {
        /** @var array<string, array<string, mixed>> $value */
        $value = config($key, []);

        return $value;
    }

    /**
     * @param  array<string, string|int>  $replace
     * @return array<string, string|int>
     */
    private static function withApp(array $replace): array
    {
        return ['app' => (string) config('app.name'), ...$replace];
    }

    /**
     * Placeholders filled literally, without the translator: an admin's
     * sentence is not a translation key, and must reach the recipient
     * exactly as they wrote it.
     *
     * @param  array<string, string|int>  $replace
     */
    private static function fill(string $text, array $replace): string
    {
        $pairs = [];

        foreach (self::withApp($replace) as $key => $value) {
            $pairs[':'.$key] = (string) $value;
        }

        // strtr tries the longest key first, so :listing never eats :list.
        return strtr($text, $pairs);
    }
}
