<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Jobs\ClassifyTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class BulkClassifyTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:bulk-classify
                            {--unclassified : Only classify tickets without a category}
                            {--limit=50 : Maximum number of tickets to process}
                            {--delay=1 : Delay in seconds between job dispatches}
                            {--dry-run : Show what would be processed without actually queuing jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk classify tickets using AI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $unclassifiedOnly = $this->option('unclassified');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $isDryRun = $this->option('dry-run');

        // Build query
        $query = Ticket::query();
        
        if ($unclassifiedOnly) {
            $query->whereNull('category');
        }

        $query->orderBy('created_at', 'desc')->limit($limit);

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets found to classify.');
            return Command::SUCCESS;
        }

        $totalTickets = $tickets->count();

        if ($isDryRun) {
            $this->info("DRY RUN - Would process {$totalTickets} tickets:");
            $this->table(
                ['ID', 'Subject', 'Status', 'Current Category'],
                $tickets->map(fn($ticket) => [
                    $ticket->id,
                    \Str::limit($ticket->subject, 50),
                    $ticket->status,
                    $ticket->category ?? 'None'
                ])->toArray()
            );
            return Command::SUCCESS;
        }

        $this->info("Processing {$totalTickets} tickets for AI classification...");

        $progressBar = $this->output->createProgressBar($totalTickets);
        $progressBar->start();

        $processed = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            try {
                // Dispatch classification job
                ClassifyTicket::dispatch($ticket);
                
                $processed++;
                
                // Add delay between dispatches to avoid rate limiting
                if ($delay > 0) {
                    sleep($delay);
                }
                
            } catch (\Exception $e) {
                $this->error("Failed to queue classification for ticket {$ticket->id}: {$e->getMessage()}");
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Show summary
        $this->info("Classification jobs queued successfully!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tickets', $totalTickets],
                ['Successfully Queued', $processed],
                ['Errors', $errors],
                ['Queue Jobs Added', $processed],
            ]
        );

        if ($processed > 0) {
            $this->info("Run 'php artisan queue:work' to process the classification jobs.");
            
            // Show queue status
            $pendingJobs = Queue::size();
            $this->comment("Current queue size: {$pendingJobs} jobs");
        }

        return Command::SUCCESS;
    }
}