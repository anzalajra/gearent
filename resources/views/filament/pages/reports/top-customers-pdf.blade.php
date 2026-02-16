<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 5px;
        }
        .meta {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated on {{ $date }}</div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">Rank</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th class="text-center">Total Bookings</th>
                <th class="text-right">Total Revenue</th>
                <th class="text-right">Avg. Transaction</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $index => $customer)
                @php
                    $totalRevenue = $customer->invoices_sum_total ?? 0;
                    $invoiceCount = $customer->invoices_count ?? 0;
                    $avgValue = $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td class="text-center">{{ $invoiceCount }}</td>
                    <td class="text-right">{{ Number::currency($totalRevenue, 'IDR') }}</td>
                    <td class="text-right">{{ Number::currency($avgValue, 'IDR') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
