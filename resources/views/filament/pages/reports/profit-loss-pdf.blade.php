<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #666;
        }
        .text-right {
            text-align: right;
        }
        .section-header td {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #1f2937;
        }
        .subtotal td {
            font-weight: bold;
            border-top: 2px solid #ddd;
        }
        .total-row td {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        .indent {
            padding-left: 24px;
        }
        .text-red {
            color: #dc2626;
        }
        .text-green {
            color: #16a34a;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <!-- REVENUE -->
            <tr class="section-header">
                <td colspan="2">REVENUE</td>
            </tr>
            @foreach($plData['revenue']['items'] as $item)
            <tr>
                <td class="indent">{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td class="indent">Total Revenue</td>
                <td class="text-right text-green">{{ number_format($plData['revenue']['total'], 0, ',', '.') }}</td>
            </tr>

            <!-- COGS -->
            <tr class="section-header">
                <td colspan="2" style="padding-top: 20px;">COST OF REVENUE (HPP)</td>
            </tr>
            @forelse($plData['cogs']['items'] as $item)
            <tr>
                <td class="indent">{{ $item['name'] }}</td>
                <td class="text-right text-red">({{ number_format($item['amount'], 0, ',', '.') }})</td>
            </tr>
            @empty
            <tr>
                <td class="indent" style="font-style: italic; color: #999;">No cost of revenue recorded</td>
                <td class="text-right">-</td>
            </tr>
            @endforelse
            <tr class="subtotal">
                <td class="indent">Gross Profit</td>
                <td class="text-right">{{ number_format($plData['gross_profit'], 0, ',', '.') }}</td>
            </tr>

            <!-- EXPENSES -->
            <tr class="section-header">
                <td colspan="2" style="padding-top: 20px;">OPERATING EXPENSES</td>
            </tr>
            @forelse($plData['expenses']['items'] as $item)
            <tr>
                <td class="indent">{{ $item['name'] }}</td>
                <td class="text-right text-red">({{ number_format($item['amount'], 0, ',', '.') }})</td>
            </tr>
            @empty
            <tr>
                <td class="indent" style="font-style: italic; color: #999;">No operating expenses recorded</td>
                <td class="text-right">-</td>
            </tr>
            @endforelse
            <tr class="subtotal">
                <td class="indent">Total Operating Expenses</td>
                <td class="text-right text-red">({{ number_format($plData['expenses']['total'], 0, ',', '.') }})</td>
            </tr>

            <!-- NET PROFIT -->
            <tr class="total-row">
                <td style="padding-top: 15px; padding-bottom: 15px;">NET PROFIT</td>
                <td class="text-right {{ $plData['net_profit'] >= 0 ? 'text-green' : 'text-red' }}" style="padding-top: 15px; padding-bottom: 15px;">
                    {{ number_format($plData['net_profit'], 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This document is automatically generated by the system.</p>
    </div>
</body>
</html>
