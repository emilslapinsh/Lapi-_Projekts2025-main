<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function fetchEvents(Request $request)
    {
        $title = $request->input('title');

        $query = Event::where('user_id', auth()->id());

        if ($title) {
            $query->where('title', $title);
        }

        $events = $query->get();

        $formattedEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->date,
                'description' => $event->description
            ];
        });

        return response()->json($formattedEvents);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        Event::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'date' => $validatedData['date'],
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }
}
