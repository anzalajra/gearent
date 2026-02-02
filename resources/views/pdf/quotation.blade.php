<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation - {{ $rental->rental_code }}</title>
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
        .info-section {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
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
        .items-table .text-right {
            text-align: right;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 8px;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .terms {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .terms h4 {
            margin: 0 0 10px 0;
        }
        .terms ul {
            margin: 0;
            padding-left: 20px;
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
        <h1>Quotation</h1>
        <p>Penawaran Harga Sewa Alat</p>
        <p><strong>{{ $rental->rental_code }}</strong></p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Tanggal</td>
                <td>: {{ now()->format('d F Y') }}</td>
                <td class="label">Valid Hingga</td>
                <td>: {{ now()->addDays(7)->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Kepada</td>
                <td>: {{ $rental->customer->name }}</td>
                <td class="label">Phone</td>
                <td>: {{ $rental->customer->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td colspan="3">: {{ $rental->customer->address ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode Rental</td>
                <td colspan="3">: {{ $rental->start_date->format('d M Y H:i') }} - {{ $rental->end_date->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 15%;">Serial Number</th>
                <th style="width: 15%;" class="text-right">Harga/Hari</th>
                <th style="width: 10%;" class="text-right">Hari</th>
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

    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($rental->discount > 0)
        <tr>
            <td>Diskon</td>
            <td class="text-right">- Rp {{ number_format($rental->discount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
        </tr>
        @if($rental->deposit > 0)
        <tr>
            <td>Deposit</td>
            <td class="text-right">Rp {{ number_format($rental->deposit, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <div class="terms">
        <h4>Syarat & Ketentuan:</h4>
        <ul>
            <li>Harga belum termasuk ongkos kirim</li>
            <li>Pembayaran dilakukan di muka sebelum barang dikirim</li>
            <li>Deposit akan dikembalikan setelah barang dikembalikan dalam kondisi baik</li>
            <li>Kerusakan akibat kelalaian akan dikenakan biaya penggantian</li>
            <li>Quotation ini berlaku selama 7 hari</li>
        </ul>
    </div>

    @if($rental->notes)
    <div class="terms" style="margin-top: 15px;">
        <h4>Catatan:</h4>
        <p>{{ $rental->notes }}</p>
    </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>