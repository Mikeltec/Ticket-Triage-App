<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get dashboard statistics for tickets.
     */
    public function index(): JsonResponse
    {
        // Get total ticket count
        $totalTickets = Ticket::count();

        // Get tickets by status
        $ticketsByStatus = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are present with 0 count if missing
        foreach (Ticket::STATUSES as $status => $label) {
            if (!isset($ticketsByStatus[$status])) {
                $ticketsByStatus[$status] = 0;
            }
        }

        // Get tickets by category (only where category is not null)
        $ticketsByCategory = Ticket::select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Get recent activity (last 7 days)
        $recentActivity = Ticket::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Get classification metrics
        $classificationStats = [
            'classified_count' => Ticket::whereNotNull('category')->count(),
            'unclassified_count' => Ticket::whereNull('category')->count(),
            'average_confidence' => Ticket::whereNotNull('confidence')
                ->avg('confidence'),
        ];

        // Get tickets with notes count
        $ticketsWithNotes = Ticket::whereNotNull('note')
            ->where('note', '!=', '')
            ->count();

        return response()->json([
            'total_tickets' => $totalTickets,
            'tickets_by_status' => $ticketsByStatus,
            'tickets_by_category' => $ticketsByCategory,
            'recent_activity' => $recentActivity,
            'classification_stats' => $classificationStats,
            'tickets_with_notes' => $ticketsWithNotes,
            'generated_at' => now()->toISOString(),
        ]);
    }
}