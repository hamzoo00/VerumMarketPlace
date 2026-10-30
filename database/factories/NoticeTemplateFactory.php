<?php

namespace Database\Factories;

use App\Models\NoticeTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NoticeTemplate> */
class NoticeTemplateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'kind' => 'warning',
            'subject' => 'A note about your :app account',
            'body' => 'We have looked at your account and need you to follow the marketplace rules.',
        ];
    }
}
