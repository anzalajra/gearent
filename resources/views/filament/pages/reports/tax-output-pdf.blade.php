<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { margin-bottom: 20px; text-align: center; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 5px 0; color: #666; font-size: 0.9em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 9pt; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $title }}</h2>
        <p>Period: {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
        <p>Generated on: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Tax Invoice #</th>
                <th class="text-right">DPP (Tax Base)</th>
                <th class="text-right">PPN (VAT)</th>
                <th class="text-right">PPh 23</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $invoice)
                <tr>
                    <td>{{ $invoice->date->format('d/m/Y') }}</td>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->user->name ?? '-' }}</td>
                    <td>{{ $invoice->tax_invoice_number ?? '-' }}</td>
                    <td class="text-right">{{ Number::currency($invoice->tax_base, 'IDR') }}</td>
                    <td class="text-right">{{ Number::currency($invoice->ppn_amount, 'IDR') }}</td>
                    <td class="text-right">{{ Number::currency($invoice->pph_amount, 'IDR') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right">{{ Number::currency($records->sum('tax_base'), 'IDR') }}</td>
                <td class="text-right">{{ Number::currency($records->sum('ppn_amount'), 'IDR') }}</td>
                <td class="text-right">{{ Number::currency($records->sum('pph_amount'), 'IDR') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>