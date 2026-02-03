<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $rental->rental_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            overflow: hidden;
        }
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #2563eb;
        }
        .invoice-info {
            margin-top: 10px;
        }
        .invoice-info p {
            margin: 3px 0;
        }
        .billing-section {
            margin-bottom: 30px;
            overflow: hidden;
        }
        .billing-box {
            float: left;
            width: 48%;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .billing-box:last-child {
            float: right;
        }
        .billing-box h4 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #2563eb;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 12px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals-section {
            overflow: hidden;
            margin-top: 20px;
        }
        .totals-table {
            float: right;
            width: 300px;
        }
        .totals-table td {
            padding: 8px;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 16px;
            background: #2563eb;
            color: white;
        }
        .totals-table .total-row td {
            padding: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .notes {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>INVOICE</h1>
            <div class="invoice-info">
                <p><strong>Invoice #:</strong> INV-{{ $rental->rental_code }}</p>
                <p><strong>Tanggal:</strong> {{ now()->format('d F Y') }}</p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-{{ $rental->status === 'completed' ? 'paid' : 'unpaid' }}">
                        {{ $rental->status === 'completed' ? 'LUNAS' : 'BELUM LUNAS' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="header-right">
            <p><strong>GEARENT</strong></p>
            <p>Jl. Contoh No. 123</p>
            <p>Jakarta, Indonesia</p>
            <p>Phone: 021-1234567</p>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="billing-section">
        <div class="billing-box">
            <h4>Tagihan Kepada:</h4>
            <p><strong>{{ $rental->customer->name }}</strong></p>
            <p>{{ $rental->customer->address ?? '-' }}</p>
            <p>Phone: {{ $rental->customer->phone ?? '-' }}</p>
            <p>Email: {{ $rental->customer->email ?? '-' }}</p>
        </div>
        <div class="billing-box">
            <h4>Detail Rental:</h4>
            <p><strong>Rental Code:</strong> {{ $rental->rental_code }}</p>
            <p><strong>Periode:</strong></p>
            <p>{{ $rental->start_date->format('d M Y H:i') }} -</p>
            <p>{{ $rental->end_date->format('d M Y H:i') }}</p>
        </div>
    </div>

    <div style="clear: both;"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Item</th>
                <th style="width: 20%;">Serial Number</th>
                <th style="width: 15%;" class="text-right">Harga/Hari</th>
                <th style="width: 10%;" class="text-right">Hari</th>
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

    <div class="totals-section">
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
                <td>TOTAL</td>
                <td class="text-right">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
            </tr>
            @if($rental->deposit > 0)
            <tr>
                <td>Deposit</td>
                <td class="text-right">Rp {{ number_format($rental->deposit, 0, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="clear: both;"></div>

    @if($rental->notes)
    <div class="notes">
        <strong>Catatan:</strong><br>
        {{ $rental->notes }}
    </div>
    @endif

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda menggunakan layanan kami.</p>
        <p>Invoice ini sah tanpa tanda tangan dan cap.</p>
    </div>
</body>
</html>