<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ticket $ticket
    ) {
        // Set queue name for AI jobs
        $this->onQueue('ai-classification');
    }

    /**
     * Execute the job.
     */
    public function handle(TicketClassifier $classifier): void
    {
        Log::info('Starting ticket classification', [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
        ]);

        try {
            // Get classification from the service
            $classification = $classifier->classify(
                $this->ticket->subject,
                $this->ticket->body
            );

            // Prepare update data
            $updateData = [
                'explanation' => $classification['explanation'],
                'confidence' => $classification['confidence'],
            ];

            // Only update category if user hasn't manually set it
            // This preserves manual overrides while updating AI insights
            if (is_null($this->ticket->category)) {
                $updateData['category'] = $classification['category'];
            }

            // Update the ticket
            $this->ticket->update($updateData);

            Log::info('Ticket classification completed', [
                'ticket_id' => $this->ticket->id,
                'category' => $classification['category'],
                'confidence' => $classification['confidence'],
                'manual_override' => !is_null($this->ticket->category),
            ]);

        } catch (\Exception $e) {
            Log::error('Ticket classification failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ticket classification job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['ticket-classification', "ticket:{$this->ticket->id}"];
    }
}