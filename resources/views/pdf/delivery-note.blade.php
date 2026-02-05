@extends('pdf.layout')

@section('title', $delivery->delivery_number)

@section('content')
    <div class="document-title text-center">SURAT JALAN</div>
    <div class="text-center mb-4" style="color: #666;">
        {{ $delivery->type === 'out' ? 'BARANG KELUAR' : 'BARANG MASUK' }}
        <br>
        <strong>{{ $delivery->delivery_number }}</strong>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="meta-box" style="margin-right: 10px;">
                <div class="meta-title">Delivery Info</div>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="padding: 2px; border: none; width: 80px;">Tanggal</td>
                        <td style="padding: 2px; border: none;">: {{ $delivery->date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px; border: none;">Code</td>
                        <td style="padding: 2px; border: none;">: {{ $delivery->rental->rental_code }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px; border: none;">Period</td>
                        <td style="padding: 2px; border: none;">: {{ $delivery->rental->start_date->format('d/m/y') }} - {{ $delivery->rental->end_date->format('d/m/y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="col-6">
            <div class="meta-box" style="margin-left: 10px;">
                <div class="meta-title">Customer Info</div>
                <p class="mb-1"><strong>{{ $delivery->rental->customer->name }}</strong></p>
                <p class="mb-1">{{ $delivery->rental->customer->address ?? '-' }}</p>
                <p class="mb-1">Phone: {{ $delivery->rental->customer->phone ?? '-' }}</p>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 20%;">Serial Number</th>
                <th style="width: 15%;">Kondisi</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 15%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($delivery->items as $item)
                @if(!$item->rentalItemKit)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td><strong>{{ $item->rentalItem->productUnit->product->name }}</strong></td>
                    <td>{{ $item->rentalItem->productUnit->serial_number }}</td>
                    <td>{{ $item->condition ? ucfirst($item->condition) : '-' }}</td>
                    <td>
                        <span class="badge {{ $item->is_checked ? 'badge-success' : 'badge-danger' }}">
                            {{ $item->is_checked ? 'Checked' : 'Unchecked' }}
                        </span>
                    </td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @foreach($delivery->items->where('rental_item_id', $item->rental_item_id)->whereNotNull('rental_item_kit_id') as $kitItem)
                <tr style="background-color: {{ $doc_settings['doc_secondary_color'] ?? '#fafafa' }};">
                    <td></td>
                    <td style="padding-left: 20px; font-size: 11px;">
                        <span style="display:inline-block; width: 8px; height: 8px; border-left: 1px solid #666; border-bottom: 1px solid #666; margin-right: 2px; margin-bottom: 4px;">&nbsp;</span>
                        {{ $kitItem->rentalItemKit->unitKit->name }}
                    </td>
                    <td style="font-size: 11px;">{{ $kitItem->rentalItemKit->unitKit->serial_number ?? '-' }}</td>
                    <td style="font-size: 11px;">{{ $kitItem->condition ? ucfirst($kitItem->condition) : '-' }}</td>
                    <td>
                        <span class="badge {{ $kitItem->is_checked ? 'badge-success' : 'badge-danger' }}">
                            {{ $kitItem->is_checked ? '✓' : '✗' }}
                        </span>
                    </td>
                    <td style="font-size: 11px;">{{ $kitItem->notes ?? '-' }}</td>
                </tr>
                @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    @if($delivery->notes)
    <div class="meta-box" style="margin-top: 20px;">
        <div class="meta-title">Catatan</div>
        {{ $delivery->notes }}
    </div>
    @endif

    <div style="margin-top: 50px; page-break-inside: avoid;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: center; border: none; width: 33%;">
                    <p>Yang Menyerahkan</p>
                    <div style="margin-top: 60px; border-top: 1px solid #333; width: 80%; margin-left: auto; margin-right: auto;">
                        {{ $delivery->checkedBy?->name ?? '________________' }}
                    </div>
                </td>
                <td style="text-align: center; border: none; width: 33%;">
                    <p>Yang Menerima</p>
                    <div style="margin-top: 60px; border-top: 1px solid #333; width: 80%; margin-left: auto; margin-right: auto;">
                        {{ $delivery->rental->customer->name }}
                    </div>
                </td>
                <td style="text-align: center; border: none; width: 33%;">
                    <p>Mengetahui</p>
                    <div style="margin-top: 60px; border-top: 1px solid #333; width: 80%; margin-left: auto; margin-right: auto;">
                        ________________
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection
