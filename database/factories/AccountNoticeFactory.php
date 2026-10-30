<?php

namespace Database\Factories;

use App\Models\AccountNotice;
use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Support\NoticeCatalogue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccountNotice> */
class AccountNoticeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'recipient_type' => (new SellerAccount)->getMorphClass(),
            'recipient_id' => SellerAccount::factory(),
            'kind' => 'warning',
            'body' => NoticeCatalogue::defaultBody('warning'),
            'used_default' => true,
        ];
    }

    /** Addressed to a particular account. */
    public function to(SellerAccount|Buyer $recipient): static
    {
        return $this->state(fn (): array => [
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
        ]);
    }
}
