<?php

namespace App\Console\Commands;

use App\Actions\Sellers\ApproveSellerApplication;
use App\Models\SellerApplication;
use Illuminate\Console\Command;

/**
 * Admin review from the CLI until the admin panel exists.
 */
class ReviewSellerApplications extends Command
{
    protected $signature = 'seller:review
        {reference? : The application reference, e.g. APP-2026-4821}
        {--approve : Approve the application}
        {--reject= : Reject it with this reason}
        {--reviewer=cli : Who made the decision}';

    protected $description = 'List or decide pending seller applications';

    public function handle(ApproveSellerApplication $approver): int
    {
        $reference = $this->argument('reference');
        $reference = is_string($reference) ? $reference : null;
        $reviewer = is_string($this->option('reviewer')) ? $this->option('reviewer') : null;

        if ($reference === null) {
            return $this->listPending();
        }

        $application = SellerApplication::with('sellerAccount')
            ->where('reference', $reference)
            ->first();

        if ($application === null) {
            $this->error("No application found for {$reference}.");

            return self::FAILURE;
        }

        $rejection = $this->option('reject');

        if (is_string($rejection) && $rejection !== '') {
            $approver->reject($application, $rejection, $reviewer);
            $this->warn("Rejected {$reference}.");

            return self::SUCCESS;
        }

        if (! $this->option('approve')) {
            $this->showDetails($application);

            return self::SUCCESS;
        }

        $storefront = $approver->handle($application, $reviewer);

        $this->info("Approved {$reference}.");
        $this->line("Storefront: /sellers/{$storefront->slug}");

        return self::SUCCESS;
    }

    private function listPending(): int
    {
        $pending = SellerApplication::with('sellerAccount')->pending()->get();

        if ($pending->isEmpty()) {
            $this->info('No applications waiting for review.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reference', 'Business', 'Country', 'Applicant', 'Submitted'],
            $pending->map(fn (SellerApplication $application): array => [
                $application->reference,
                $application->business_name,
                $application->country,
                $application->sellerAccount?->email,
                $application->submitted_at?->diffForHumans(),
            ])->all(),
        );

        $this->newLine();
        $this->line('Approve with: <info>php artisan seller:review APP-… --approve</info>');

        return self::SUCCESS;
    }

    private function showDetails(SellerApplication $application): void
    {
        $this->table(['Field', 'Value'], [
            ['Reference', $application->reference],
            ['Status', $application->status],
            ['Business', $application->business_name],
            ['Country', $application->country],
            ['Trade license', $application->trade_license_number ?? '—'],
            ['Specialties', $application->specialties ?? '—'],
            ['Applicant', $application->sellerAccount?->name],
            ['Email', $application->sellerAccount?->email],
            ['Submitted', $application->submitted_at?->toDayDateTimeString()],
        ]);

        $this->newLine();
        $this->line('Approve: <info>--approve</info>   Reject: <info>--reject="reason"</info>');
    }
}
