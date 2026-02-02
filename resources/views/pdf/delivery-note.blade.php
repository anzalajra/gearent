<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $delivery->delivery_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .items-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .kit-row td {
            padding-left: 30px;
            font-size: 11px;
            color: #555;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-out { background: #fef3c7; color: #92400e; }
        .badge-in { background: #d1fae5; color: #065f46; }
        .badge-checked { background: #d1fae5; color: #065f46; }
        .badge-unchecked { background: #fee2e2; color: #991b1b; }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            padding: 20px;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        .notes {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding: 10px 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Surat Jalan</h1>
        <p>{{ $delivery->type === 'out' ? 'BARANG KELUAR' : 'BARANG MASUK' }}</p>
        <p><strong>{{ $delivery->delivery_number }}</strong></p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ $delivery->date->format('d F Y') }}</td>
            <td class="label">Rental Code</td>
            <td>: {{ $delivery->rental->rental_code }}</td>
        </tr>
        <tr>
            <td class="label">Customer</td>
            <td>: {{ $delivery->rental->customer->name }}</td>
            <td class="label">Phone</td>
            <td>: {{ $delivery->rental->customer->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td colspan="3">: {{ $delivery->rental->customer->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode Rental</td>
            <td colspan="3">: {{ $delivery->rental->start_date->format('d M Y H:i') }} - {{ $delivery->rental->end_date->format('d M Y H:i') }}</td>
        </tr>
    </table>

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
                        <span class="badge {{ $item->is_checked ? 'badge-checked' : 'badge-unchecked' }}">
                            {{ $item->is_checked ? '✓ Checked' : '✗ Unchecked' }}
                        </span>
                    </td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @foreach($delivery->items->where('rental_item_id', $item->rental_item_id)->whereNotNull('rental_item_kit_id') as $kitItem)
                <tr class="kit-row">
                    <td></td>
                    <td>↳ {{ $kitItem->rentalItemKit->unitKit->name }}</td>
                    <td>{{ $kitItem->rentalItemKit->unitKit->serial_number ?? '-' }}</td>
                    <td>{{ $kitItem->condition ? ucfirst($kitItem->condition) : '-' }}</td>
                    <td>
                        <span class="badge {{ $kitItem->is_checked ? 'badge-checked' : 'badge-unchecked' }}">
                            {{ $kitItem->is_checked ? '✓' : '✗' }}
                        </span>
                    </td>
                    <td>{{ $kitItem->notes ?? '-' }}</td>
                </tr>
                @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    @if($delivery->notes)
    <div class="notes">
        <strong>Catatan:</strong><br>
        {{ $delivery->notes }}
    </div>
    @endif

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <p>Yang Menyerahkan</p>
                    <div class="signature-line">
                        {{ $delivery->checkedBy?->name ?? '________________' }}
                    </div>
                </td>
                <td>
                    <p>Yang Menerima</p>
                    <div class="signature-line">
                        {{ $delivery->rental->customer->name }}
                    </div>
                </td>
                <td>
                    <p>Mengetahui</p>
                    <div class="signature-line">
                        ________________
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>