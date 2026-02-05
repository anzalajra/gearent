@extends('pdf.layout')

@section('title', 'Invoice - ' . $rental->rental_code)

@section('content')
    <div class="document-title">INVOICE</div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="meta-box" style="margin-right: 10px;">
                <div class="meta-title">Invoice Details</div>
                <p class="mb-1"><strong>Invoice #:</strong> INV-{{ $rental->rental_code }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ now()->format('d F Y') }}</p>
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge {{ $rental->status === 'completed' ? 'badge-success' : 'badge-danger' }}">
                        {{ $rental->status === 'completed' ? 'LUNAS' : 'BELUM LUNAS' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="col-6">
            <div class="meta-box" style="margin-left: 10px;">
                <div class="meta-title">Bill To</div>
                <p class="mb-1"><strong>{{ $rental->customer->name }}</strong></p>
                <p class="mb-1">{{ $rental->customer->address ?? '-' }}</p>
                <p class="mb-1">Phone: {{ $rental->customer->phone ?? '-' }}</p>
                <p class="mb-1">Email: {{ $rental->customer->email ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="meta-box" style="margin-right: 10px;">
                <div class="meta-title">Rental Period</div>
                <p class="mb-1"><strong>Start:</strong> {{ $rental->start_date->format('d M Y H:i') }}</p>
                <p class="mb-1"><strong>End:</strong> {{ $rental->end_date->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 20%;">Serial Number</th>
                <th style="width: 15%;" class="text-right">Price/Day</th>
                <th style="width: 10%;" class="text-right">Days</th>
                <th style="width: 15%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rental->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->productUnit->product->name }}</td>
                <td>{{ $item->productUnit->serial_number }}</td>
                <td class="text-right">Rp {{ number_format($item->daily_rate, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->days }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row">
        <div class="col-6">
            @if(!empty($doc_settings['doc_bank_details']))
                <div class="meta-box" style="margin-right: 10px;">
                    <div class="meta-title">Payment Info</div>
                    <div style="font-size: 11px;">
                        {!! $doc_settings['doc_bank_details'] !!}
                    </div>
                </div>
            @endif

            @if($rental->notes)
            <div class="meta-box" style="margin-right: 10px; margin-top: 10px;">
                <div class="meta-title">Notes</div>
                <p style="font-size: 11px;">{{ $rental->notes }}</p>
            </div>
            @endif
        </div>
        <div class="col-6">
            <table style="width: 100%; margin-left: 10px;">
                <tr>
                    <td style="border: none; padding: 5px;">Subtotal</td>
                    <td style="border: none; padding: 5px;" class="text-right">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($rental->discount > 0)
                <tr>
                    <td style="border: none; padding: 5px;">Discount</td>
                    <td style="border: none; padding: 5px;" class="text-right">- Rp {{ number_format($rental->discount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="font-weight: bold; font-size: 14px; background-color: {{ $doc_settings['doc_secondary_color'] ?? '#f3f4f6' }};">
                    <td style="padding: 10px;">TOTAL</td>
                    <td style="padding: 10px;" class="text-right">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
                </tr>
                @if($rental->deposit > 0)
                <tr>
                    <td style="border: none; padding: 5px;">Deposit (Refundable)</td>
                    <td style="border: none; padding: 5px;" class="text-right">Rp {{ number_format($rental->deposit, 0, ',', '.') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
@endsection
