<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventCalendarTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function fetchEvents(Request $request)
    {
        $title = $request->input('title');

        $query = Event::query()
            ->where('user_id', auth()->id())
            ->orderBy('date')
            ->orderBy('id');

        if ($title) {
            $query->where('title', $title);
        }

        $events = $query->get();

        return response()->json(
            $events->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->date->format('Y-m-d'),
                    'extendedProps' => [
                        'description' => $event->description ?? '',
                    ],
                ];
            })
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', Rule::in(EventCalendarTypes::TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
        ]);

        Event::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $allowedTitles = array_values(array_unique(array_merge(
            EventCalendarTypes::TYPES,
            [(string) $event->title],
        )));

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', Rule::in($allowedTitles)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
        ]);

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Event $event)
    {
        $this->authorizeEvent($event);
        $event->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeEvent(Event $event): void
    {
        abort_unless((int) $event->user_id === (int) auth()->id(), 403);
    }
}
