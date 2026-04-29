<?php

namespace App\Http\Controllers;

use App\Models\FamilyEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FamilyEventController extends Controller
{
    public function index()
    {
        $events = FamilyEvent::with(['creator'])
            ->withCount([
                'rsvps as going_count' => fn ($query) => $query->where('status', 'going'),
                'rsvps as maybe_count' => fn ($query) => $query->where('status', 'maybe'),
            ])
            ->orderBy('event_date', 'asc')
            ->get();

        return Inertia::render('FamilyEvents/Index', [
            'events' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable',
            'location' => 'nullable|array',
            'status' => 'required|in:planning,confirmed,cancelled',
        ]);

        $event = Auth::user()->familyEvents()->create($validated);

        return redirect()->back()->with('success', 'Acara berhasil dibuat.');
    }

    public function show(FamilyEvent $event)
    {
        $event->load(['creator', 'rsvps.user.profile']);

        $myRsvp = $event->rsvps()->where('user_id', Auth::id())->first();

        return Inertia::render('FamilyEvents/Show', [
            'event' => $event,
            'myRsvp' => $myRsvp,
        ]);
    }

    public function update(Request $request, FamilyEvent $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable',
            'location' => 'nullable|array',
            'status' => 'required|in:planning,confirmed,cancelled',
        ]);

        $event->update($validated);

        return redirect()->back()->with('success', 'Acara berhasil diperbarui.');
    }

    public function rsvp(Request $request, FamilyEvent $event)
    {
        $validated = $request->validate([
            'status' => 'required|in:going,not_going,maybe',
            'headcount' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $event->rsvps()->updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return redirect()->back()->with('success', 'RSVP berhasil dikirim.');
    }

    public function destroy(FamilyEvent $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('family-events.index')->with('success', 'Acara berhasil dihapus.');
    }
}
