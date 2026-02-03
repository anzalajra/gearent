<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Checklist Form - {{ $rental->rental_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .header-logo {
            display: table-cell;
            width: 30%;
            vertical-align: middle;
        }
        .header-logo h2 {
            margin: 0;
            color: #333;
            font-size: 28px;
            font-weight: bold;
        }
        .header-info {
            display: table-cell;
            width: 70%;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #333;
        }
        .renter-info {
            margin-bottom: 20px;
        }
        .renter-info p {
            margin: 0;
            font-size: 11px;
        }
        .rental-code {
            font-size: 24px;
            font-weight: bold;
            color: #d32f2f;
            margin: 15px 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 2px 0;
        }
        .meta-label {
            font-weight: bold;
            width: 110px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th, .items-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 10px;
        }
        .checkbox-cell {
            text-align: center;
            width: 70px;
        }
        .checkbox-box {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 1px solid #333;
            margin: 0 auto;
        }
        .kit-row td {
            font-size: 10px;
            color: #555;
            background-color: #fafafa;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table th, .signature-table td {
            border: 1px solid #333;
            width: 25%;
            padding: 10px;
            text-align: center;
        }
        .signature-table th {
            background-color: #f5f5f5;
            font-size: 10px;
            padding: 8px;
            text-transform: uppercase;
        }
        .signature-box {
            height: 100px;
        }
        .note {
            font-style: italic;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <h2 style="margin: 0;">Warehouse</h2>
        </div>
        <div class="header-info">
            Warehouse Film dan Televisi UPI<br>
            Fakultas Pendidikan Seni dan Desain Universitas, Jl. Dr Setiabudhi, Pendidikan Indonesia No.229,<br>
            Isola, Kec. Sukasari, Kota Bandung, Jawa Barat bandung JB 40154 Indonesia
        </div>
    </div>

    <div class="renter-info">
        <p style="font-weight: bold; margin-bottom: 5px;">Equipment Renter:</p>
        <p style="font-size: 13px; font-weight: bold; margin-bottom: 2px;">{{ $rental->customer->name }}</p>
        <p>{{ $rental->customer->address ?? '-' }}</p>
    </div>

    <div class="rental-code">
        {{ $rental->rental_code }}
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Order</td>
            <td>: {{ $rental->rental_code }}</td>
            <td class="meta-label">Status</td>
            <td>: {{ ucfirst($rental->getRealTimeStatus()) }}</td>
            <td class="meta-label">Scheduled Date</td>
            <td>: {{ $rental->start_date->format('d/m/Y h:i:s A') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">PRODUCT</th>
                <th style="width: 10%; text-align: center;">QUANTITY</th>
                <th style="width: 10%; text-align: center;">PICKUP</th>
                <th style="width: 10%; text-align: center;">RETURN</th>
                <th style="width: 30%;">LOT/SERIAL NUMBER</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rental->items as $item)
                <tr>
                    <td><strong>{{ $item->productUnit->product->name }}</strong></td>
                    <td style="text-align: center;">1.00</td>
                    <td class="checkbox-cell"><div class="checkbox-box"></div></td>
                    <td class="checkbox-cell"><div class="checkbox-box"></div></td>
                    <td>{{ $item->productUnit->serial_number }}</td>
                </tr>
                @foreach($item->rentalItemKits as $kit)
                <tr class="kit-row">
                    <td style="padding-left: 20px;">↳ {{ $kit->unitKit->name }}</td>
                    <td style="text-align: center;">1.00</td>
                    <td class="checkbox-cell"><div class="checkbox-box"></div></td>
                    <td class="checkbox-cell"><div class="checkbox-box"></div></td>
                    <td>{{ $kit->unitKit->serial_number ?? '-' }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <thead>
                <tr>
                    <th>PICKUP RENTER</th>
                    <th>PICKUP CHECKER</th>
                    <th>RETURN RENTER</th>
                    <th>RETURN CHECKER</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="signature-box"></td>
                    <td class="signature-box"></td>
                    <td class="signature-box"></td>
                    <td class="signature-box"></td>
                </tr>
            </tbody>
        </table>
        <div class="note">*Isi kolom dengan nama lengkap & tanda tangan</div>
    </div>
</body>
</html>
