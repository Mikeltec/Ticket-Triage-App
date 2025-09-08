<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Jobs\ClassifyTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets with filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query();

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->byStatus($request->status);
        }

        // Apply category filter
        if ($request->has('category') && $request->category !== '') {
            $query->byCategory($request->category);
        }

        // Apply search
        if ($request->has('search') && $request->search !== '') {
            $query->search($request->search);
        }

        // Sort by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $tickets = $query->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'status' => 'open', // Default status
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(Ticket::STATUSES))],
            'category' => 'sometimes|nullable|string|max:255',
            'note' => 'sometimes|nullable|string',
        ]);

        $ticket->update($validated);

        return response()->json($ticket);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Trigger AI classification for a specific ticket.
     */
    public function classify(string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        // Dispatch the classification job
        ClassifyTicket::dispatch($ticket);

        return response()->json([
            'message' => 'Classification job queued successfully',
            'ticket_id' => $ticket->id
        ]);
    }
}