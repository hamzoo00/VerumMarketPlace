<?php

namespace App\Rules;

use App\Models\BlockedEmail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses an email an admin has barred from this signup flow.
 *
 * Deliberately vague to the applicant: naming the block invites people to
 * probe which addresses are barred.
 */
class NotBlockedEmail implements ValidationRule
{
    public function __construct(
        private readonly string $scope = BlockedEmail::SCOPE_SELLER,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (BlockedEmail::isBlocked($value, $this->scope)) {
            $fail(__('This email address cannot be used to apply. Contact us if you think this is a mistake.'));
        }
    }
}
