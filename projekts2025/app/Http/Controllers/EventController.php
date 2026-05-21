<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventCalendarTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Kalendāra notikumu API
// Nodrošina notikumu ielādi FullCalendar, kā arī pievienošanu, labošanu un dzēšanu ar piekļuves pārbaudi
class EventController extends Controller
{
    // Atgriež notikumus kalendāram JSON formātā
    public function fetchEvents(Request $request)
    {
        // Nolasa filtru pēc notikuma tipa (title)
        $title = $request->input('title');

        // Atlasa tikai šī lietotāja notikumus
        $query = Event::query()
            ->where('user_id', auth()->id())
            ->orderBy('date')
            ->orderBy('id');

        // Ja ir filtrs, atstāj tikai konkrēto tipu
        if ($title) {
            $query->where('title', $title);
        }

        // Iegūst notikumus no datubāzes
        $events = $query->get();

        // Pārveido notikumus FullCalendar formātā
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

    // Izveido jaunu notikumu (kalendārā)
    public function store(Request $request)
    {
        // Validē notikuma datus
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', Rule::in(EventCalendarTypes::TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
        ]);

        // Izveido jaunu ierakstu un piesaista to lietotājam
        Event::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
            'user_id' => auth()->id(),
        ]);

        // Atgriež veiksmīgu rezultātu
        return response()->json(['success' => true]);
    }

    // Atjaunina esošu notikumu
    public function update(Request $request, Event $event)
    {
        // Pārbauda, vai notikums pieder lietotājam
        $this->authorizeEvent($event);

        // Atļauj arī veco nosaukumu, ja tas jau ir saglabāts datubāzē
        $allowedTitles = array_values(array_unique(array_merge(
            EventCalendarTypes::TYPES,
            [(string) $event->title],
        )));

        // Validē jaunās vērtības
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', Rule::in($allowedTitles)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
        ]);

        // Saglabā izmaiņas
        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
        ]);

        // Atgriež veiksmīgu rezultātu
        return response()->json(['success' => true]);
    }

    // Dzēš notikumu
    public function destroy(Event $event)
    {
        // Pārbauda piekļuvi un dzēš ierakstu
        $this->authorizeEvent($event);
        $event->delete();

        // Atgriež veiksmīgu rezultātu
        return response()->json(['success' => true]);
    }

    // Atļauj darbības tikai ar saviem notikumiem
    private function authorizeEvent(Event $event): void
    {
        // Bloķē piekļuvi, ja notikums nepieder lietotājam
        abort_unless((int) $event->user_id === (int) auth()->id(), 403);
    }
}
