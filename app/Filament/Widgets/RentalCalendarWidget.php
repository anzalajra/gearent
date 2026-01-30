<?php

namespace App\Filament\Widgets;

use App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;

class RentalCalendarWidget extends FullCalendarWidget
{
    // agar PHP 8.2 tidak error ketika trait/plugin mengakses $record
    public Model|int|string|null $record = null;

    public function fetchEvents(array $info): array
    {
        $start = $info['start'] ?? null;
        $end = $info['end'] ?? null;

        $query = Rental::query()->with(['customer', 'items.productUnit.product']);

        if ($start && $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                  ->where('end_date', '>', $start);
            });
        }

        $rentals = $query->whereIn('status', ['pending', 'active', 'completed'])->get();

        return $rentals->map(function (Rental $rental) {
            $color = match ($rental->status) {
                'pending' => '#f59e0b',
                'active' => '#10b981',
                'completed' => '#3b82f6',
                'cancelled' => '#ef4444',
                default => '#6b7280',
            };

            $items = $rental->items->map(function ($item) {
                $pu = $item->productUnit;
                return ($pu?->product?->name ?? '-') . ' (' . ($pu->serial_number ?? '-') . ')';
            })->join(', ');

            return EventData::make()
                ->id($rental->id)
                ->title($rental->customer->name . ' — ' . ($rental->rental_code ?? ''))
                ->start($rental->start_date?->toIso8601String())
                ->end($rental->end_date?->toIso8601String())
                ->backgroundColor($color)
                ->borderColor($color)
                ->extendedProps([
                    'status' => $rental->status,
                    'items' => $items,
                    'total' => 'Rp ' . number_format($rental->total ?? 0, 0, ',', '.'),
                ]);
        })->toArray();
    }

    public function eventDidMount(): string
    {
        // Pasang click listener pada tiap event (capture) untuk mencegah modal bawaan.
        // Juga lakukan satu kali setup global: intercept klik pada toolbar/create button agar redirect ke create page.
        return <<<JS
            function({ event, el, view }) {
                // tooltip + pointer
                el.setAttribute('title', event.title + " — " + (event.extendedProps.items || ""));
                el.style.cursor = 'pointer';

                // pastikan klik event langsung meredirect dan mencegah handler plugin lainnya
                el.addEventListener('click', function(ev) {
                    try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
                    window.location.href = '/admin/rentals/' + event.id + '/edit';
                }, true);

                // satu kali global setup (per halaman) untuk intercept tombol "Create"/"New"
                if (!window.__filament_rental_calendar_setup_done) {
                    window.__filament_rental_calendar_setup_done = true;

                    // Intercept clicks on calendar toolbar buttons (delegated capture listener).
                    document.addEventListener('click', function(e) {
                        var t = e.target;
                        // cari tombol fc-button atau button dalam toolbar
                        var btn = t.closest && t.closest('.fc-button, button, a');
                        if (!btn) return;

                        // If the button text or aria-label indicates a Create/New action, intercept it.
                        var text = (btn.innerText || btn.textContent || '').trim().toLowerCase();
                        var aria = (btn.getAttribute && btn.getAttribute('aria-label') || '').trim().toLowerCase();

                        if (text.includes('create') || text.includes('new') || aria.includes('create') || aria.includes('new')) {
                            try { e.preventDefault(); e.stopImmediatePropagation(); } catch(err) {}
                            // redirect to Filament create page
                            window.location.href = '/admin/rentals/create';
                        }
                    }, true);

                    // Also intercept clicks that open modal content via Livewire (modal close/open buttons not always fc-button)
                    // Watch DOM mutations: if a Filament modal with title "Create" appears, redirect to create page.
                    try {
                        var observer = new MutationObserver(function(mutations) {
                            for (var i=0;i<mutations.length;i++){
                                var m = mutations[i];
                                for (var j=0;j<m.addedNodes.length;j++){
                                    var node = m.addedNodes[j];
                                    if (!(node instanceof HTMLElement)) continue;
                                    // Filament modal root often contains role="dialog" or class 'filament-modal'
                                    if (node.matches && (node.matches('.filament-modal') || node.querySelector && node.querySelector('[data-testid="filament-modal"]'))) {
                                        var titleEl = node.querySelector && (node.querySelector('.filament-modal__heading, h2, h3') || node.querySelector('[data-testid="modal-title"]'));
                                        var title = titleEl ? (titleEl.innerText || '').trim().toLowerCase() : '';
                                        if (title === 'create' || title.includes('create')) {
                                            // close the modal if possible (try clicking cancel), then redirect
                                            var cancelBtn = node.querySelector('button:contains("Cancel"), button:contains("cancel")');
                                            // just redirect immediately (user will land on full create form)
                                            window.location.href = '/admin/rentals/create';
                                        }
                                    }
                                }
                            }
                        });
                        observer.observe(document.body, { childList: true, subtree: true });
                    } catch (e) {
                        // ignore observer errors in older browsers
                    }
                }
            }
        JS;
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            // toolbar tanpa tombol create (we handle create via blade button or global intercept)
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek',
            ],
            // pastikan interaksi yang memicu create dimatikan
            'selectable' => false,
            'editable' => false,
            'selectMirror' => false,
            // handle dateClick: redirect to create with start date
            'dateClick' => "function(info) {
                try {
                    if (info.jsEvent) { info.jsEvent.preventDefault(); info.jsEvent.stopPropagation(); }
                } catch(e){}
                var params = new URLSearchParams();
                params.set('start', info.dateStr);
                window.location.href = '/admin/rentals/create?' + params.toString();
            }",
            'eventDisplay' => 'block',
        ];
    }
}