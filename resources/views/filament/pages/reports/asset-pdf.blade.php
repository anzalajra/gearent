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
                <th>Asset Name</th>
                @if($type === 'utilization')
                    <th class="text-center">Days Rented</th>
                    <th class="text-center">Utilization Rate</th>
                    <th class="text-center">Status</th>
                @elseif($type === 'revenue')
                    <th class="text-right">Total Revenue</th>
                    <th class="text-center">ROI</th>
                @elseif($type === 'maintenance')
                    <th class="text-right">Total Cost</th>
                    <th class="text-center">Frequency</th>
                    <th class="text-center">Last Maintenance</th>
                    <th class="text-center">Status</th>
                @elseif($type === 'depreciation')
                    <th class="text-right">Purchase Price</th>
                    <th class="text-right">Monthly Depr.</th>
                    <th class="text-right">Accumulated Depr.</th>
                    <th class="text-right">Book Value</th>
                @elseif($type === 'lost_damaged')
                    <th class="text-center">Condition</th>
                    <th class="text-center">Date Reported</th>
                    <th class="text-right">Purchase Price</th>
                    <th class="text-right">Accumulated Depr.</th>
                    <th class="text-right">Book Value (Loss)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                @php $metric = $item[$type]; @endphp
                <tr>
                    <td>{{ $metric['name'] }}</td>
                    
                    @if($type === 'utilization')
                        <td class="text-center">{{ $metric['days_rented'] }}</td>
                        <td class="text-center">{{ $metric['utilization_rate'] }}%</td>
                        <td class="text-center">{{ $metric['status'] }}</td>
                    @elseif($type === 'revenue')
                        <td class="text-right">{{ Number::currency($metric['revenue'], 'IDR') }}</td>
                        <td class="text-center">{{ $metric['roi'] }}%</td>
                    @elseif($type === 'maintenance')
                        <td class="text-right">{{ Number::currency($metric['total_cost'], 'IDR') }}</td>
                        <td class="text-center">{{ $metric['frequency'] }}</td>
                        <td class="text-center">{{ $metric['last_maintenance'] ? $metric['last_maintenance']->format('d M Y') : '-' }}</td>
                        <td class="text-center">{{ $metric['status'] }}</td>
                    @elseif($type === 'depreciation')
                        <td class="text-right">{{ Number::currency($metric['purchase_price'], 'IDR') }}</td>
                        <td class="text-right">{{ Number::currency($metric['monthly_depreciation'], 'IDR') }}</td>
                        <td class="text-right">{{ Number::currency($metric['accumulated_depreciation'], 'IDR') }}</td>
                        <td class="text-right">{{ Number::currency($metric['book_value'], 'IDR') }}</td>
                    @elseif($type === 'lost_damaged')
                        <td class="text-center">{{ $metric['condition'] }}</td>
                        <td class="text-center">{{ $metric['date_reported'] ? $metric['date_reported']->format('d M Y') : '-' }}</td>
                        <td class="text-right">{{ Number::currency($metric['purchase_price'], 'IDR') }}</td>
                        <td class="text-right">{{ Number::currency($metric['accumulated_depreciation'], 'IDR') }}</td>
                        <td class="text-right font-bold text-red-600">{{ Number::currency($metric['book_value'], 'IDR') }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>