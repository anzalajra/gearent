<?php

namespace App\Filament\Widgets;

use App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class RentalCalendarWidget extends FullCalendarWidget implements HasActions
{
    use InteractsWithActions;

    public function getRecord(): ?Model
    {
        return null;
    }

    protected function modalActions(): array
    {
        return [];
    }

    protected function viewAction(): Action
    {
        return Action::make('view')->hidden();
    }

    public function onEventClick(array $event): void
    {
        $this->mountAction('viewRentalDetails', ['rentalId' => $event['id']]);
    }

    public function viewRentalDetailsAction(): Action
    {
        return Action::make('viewRentalDetails')
            ->modalHeading('Rental Details')
            ->modalWidth('2xl')
            ->form(fn (array $arguments) => [
                Grid::make(2)
                    ->schema([
                        TextInput::make('rental_code')
                            ->label('Rental Code')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        TextInput::make('customer_name')
                            ->label('Customer')
                            ->disabled(),
                        TextInput::make('total')
                            ->label('Total Amount')
                            ->disabled(),
                        TextInput::make('start_date')
                            ->label('Start Date')
                            ->disabled(),
                        TextInput::make('end_date')
                            ->label('End Date')
                            ->disabled(),
                        Textarea::make('items')
                            ->label('Rented Units')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
            ])
            ->fillForm(function (array $arguments) {
                if (!isset($arguments['rentalId'])) return [];
                
                $rental = Rental::with(['customer', 'items.productUnit.product'])->find($arguments['rentalId']);
                if (!$rental) return [];

                $items = $rental->items->map(function ($item) {
                    $pu = $item->productUnit;
                    return ($pu?->product?->name ?? '-') . ' (' . ($pu->serial_number ?? '-') . ')';
                })->join("\n");

                return [
                    'rental_code' => $rental->rental_code,
                    'status' => ucfirst($rental->status),
                    'customer_name' => $rental->customer->name,
                    'total' => 'Rp ' . number_format($rental->total, 0, ',', '.'),
                    'start_date' => $rental->start_date->format('d M Y H:i'),
                    'end_date' => $rental->end_date->format('d M Y H:i'),
                    'items' => $items,
                    'notes' => $rental->notes,
                ];
            })
            ->modalFooterActions(fn (array $arguments) => [
                Action::make('viewRentalPage')
                    ->label('View Rental')
                    ->color('primary')
                    ->url(fn () => "/admin/rentals/{$arguments['rentalId']}/view"),
            ]);
    }

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

        $rentals = $query->get();

        return $rentals->map(function (Rental $rental) {
            $color = match ($rental->status) {
                'pending' => '#f97316', // Orange
                'confirmed' => '#3b82f6', // Blue
                'active' => '#22c55e', // Green
                'completed' => '#a855f7', // Purple
                'cancelled' => '#6b7280', // Gray
                'late_pickup' => '#ef4444', // Red
                'late_return' => '#ef4444', // Red
                'partial_return' => '#eab308', // Yellow
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

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek',
            ],
            'selectable' => false,
            'editable' => false,
            'selectMirror' => false,
            'dateClick' => "function(info) {
                try {
                    if (info.jsEvent) { info.jsEvent.preventDefault(); info.jsEvent.stopPropagation(); }
                } catch(e){}
                var params = new URLSearchParams();
                params.set('start', info.dateStr);
                window.location.href = '/admin/rentals/create?' + params.toString();
            }",
            'eventDidMount' => "function({ event, el, view }) {
                el.setAttribute('title', event.title + ' — ' + (event.extendedProps.items || ''));
                el.style.cursor = 'pointer';

                el.addEventListener('click', function(ev) {
                    try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
                    var componentId = el.closest('[wire\\\\:id]').getAttribute('wire:id');
                    Livewire.find(componentId).mountAction('viewRentalDetails', { rentalId: event.id });
                }, true);

                if (!window.__filament_rental_calendar_setup_done) {
                    window.__filament_rental_calendar_setup_done = true;
                    document.addEventListener('click', function(e) {
                        var t = e.target;
                        var btn = t.closest && t.closest('.fc-button, button, a');
                        if (!btn) return;
                        var text = (btn.innerText || btn.textContent || '').trim().toLowerCase();
                        var aria = (btn.getAttribute && btn.getAttribute('aria-label') || '').trim().toLowerCase();
                        if (text.includes('create') || text.includes('new') || aria.includes('create') || aria.includes('new')) {
                            try { e.preventDefault(); e.stopImmediatePropagation(); } catch(err) {}
                            window.location.href = '/admin/rentals/create';
                        }
                    }, true);
                    try {
                        var observer = new MutationObserver(function(mutations) {
                            for (var i=0;i<mutations.length;i++){
                                var m = mutations[i];
                                for (var j=0;j<m.addedNodes.length;j++){
                                    var node = m.addedNodes[j];
                                    if (!(node instanceof HTMLElement)) continue;
                                    if (node.matches && (node.matches('.filament-modal') || node.querySelector && node.querySelector('[data-testid=\"filament-modal\"]'))) {
                                        var titleEl = node.querySelector && (node.querySelector('.filament-modal__heading, h2, h3') || node.querySelector('[data-testid=\"modal-title\"]'));
                                        var title = titleEl ? (titleEl.innerText || '').trim().toLowerCase() : '';
                                        if (title === 'create' || title.includes('create')) {
                                            window.location.href = '/admin/rentals/create';
                                        }
                                    }
                                }
                            }
                        });
                        observer.observe(document.body, { childList: true, subtree: true });
                    } catch (e) {}
                }
            }",
            'eventDisplay' => 'block',
        ];
    }
}
