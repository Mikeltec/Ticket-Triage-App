<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 30 tickets total (more than the required 25)
        
        // 15 tickets with AI classification
        Ticket::factory(15)
            ->withClassification()
            ->create();

        // 5 tickets with classification and notes
        Ticket::factory(5)
            ->withClassification()
            ->withNote()
            ->create();

        // 7 unclassified tickets (for testing classification feature)
        Ticket::factory(7)
            ->unclassified()
            ->create();

        // 3 unclassified tickets with notes
        Ticket::factory(3)
            ->unclassified()
            ->withNote()
            ->create();

        $this->command->info('Created 30 sample tickets with mixed statuses, categories, and notes.');
    }
}