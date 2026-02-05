@extends('pdf.layout')

@section('title', 'Quotation - ' . $rental->rental_code)

@section('content')
    <div class="document-title text-center">QUOTATION</div>
    <div class="text-center mb-4" style="color: #666;">Penawaran Harga Sewa Alat</div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="meta-box" style="margin-right: 10px;">
                <div class="meta-title">Customer Info</div>
                <p class="mb-1"><strong>{{ $rental->customer->name }}</strong></p>
                <p class="mb-1">{{ $rental->customer->address ?? '-' }}</p>
                <p class="mb-1">Phone: {{ $rental->customer->phone ?? '-' }}</p>
            </div>
        </div>
        <div class="col-6">
            <div class="meta-box" style="margin-left: 10px;">
                <div class="meta-title">Quotation Details</div>
                <p class="mb-1"><strong>Code:</strong> {{ $rental->rental_code }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ now()->format('d F Y') }}</p>
                <p class="mb-1"><strong>Valid Until:</strong> {{ now()->addDays(7)->format('d F Y') }}</p>
                <p class="mb-1"><strong>Period:</strong> {{ $rental->start_date->format('d M Y H:i') }} - {{ $rental->end_date->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 15%;">Serial Number</th>
                <th style="width: 15%;" class="text-right">Price/Day</th>
                <th style="width: 10%;" class="text-right">Days</th>
                <th style="width: 20%;" class="text-right">Subtotal</th>
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
            <div class="meta-box" style="margin-right: 10px;">
                <div class="meta-title">Terms & Conditions</div>
                <ul style="padding-left: 20px; margin: 0; font-size: 11px;">
                    <li>Harga belum termasuk ongkos kirim</li>
                    <li>Pembayaran dilakukan di muka sebelum barang dikirim</li>
                    <li>Deposit akan dikembalikan setelah barang dikembalikan dalam kondisi baik</li>
                    <li>Kerusakan akibat kelalaian akan dikenakan biaya penggantian</li>
                    <li>Quotation ini berlaku selama 7 hari</li>
                </ul>
            </div>
            
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
                    <td style="padding: 10px;">Total</td>
                    <td style="padding: 10px;" class="text-right">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
                </tr>
                @if($rental->deposit > 0)
                <tr>
                    <td style="border: none; padding: 5px;">Deposit</td>
                    <td style="border: none; padding: 5px;" class="text-right">Rp {{ number_format($rental->deposit, 0, ',', '.') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
@endsection
