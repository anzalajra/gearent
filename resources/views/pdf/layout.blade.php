<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Document')</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }
        body {
            font-family: '{{ $doc_settings['doc_font_family'] ?? 'DejaVu Sans' }}', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin-top: 3.5cm;
            margin-bottom: 2cm;
            margin-left: 2cm;
            margin-right: 2cm;
        }
        
        /* Layout & Typography */
        h1, h2, h3, h4, h5, h6 {
            color: {{ $doc_settings['doc_primary_color'] ?? '#2563eb' }};
            margin: 0 0 10px 0;
        }
        
        a {
            color: {{ $doc_settings['doc_primary_color'] ?? '#2563eb' }};
            text-decoration: none;
        }

        /* Header */
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 3cm;
            padding: 0.5cm 2cm 0 2cm;
            background-color: white;
            border-bottom: 1px solid {{ $doc_settings['doc_secondary_color'] ?? '#f3f4f6' }};
        }

        .header-content {
            width: 100%;
            height: 100%;
        }

        .logo-container {
            float: left;
            width: 40%;
        }

        .logo-container img {
            max-height: 55px;
            max-width: 100%;
        }

        .company-info {
            float: right;
            width: 50%;
            text-align: right;
            font-size: 10px;
            color: #666;
            line-height: 1.2;
        }

        .company-name {
            font-weight: bold;
            font-size: 13px;
            color: {{ $doc_settings['doc_primary_color'] ?? '#2563eb' }};
            margin-bottom: 3px;
        }

        /* Footer */
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1.5cm;
            padding: 10px 2cm;
            background-color: {{ $doc_settings['doc_secondary_color'] ?? '#f3f4f6' }};
            color: #666;
            font-size: 10px;
            line-height: 1.2;
            text-align: center;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: {{ $doc_settings['doc_primary_color'] ?? '#2563eb' }};
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid {{ $doc_settings['doc_secondary_color'] ?? '#eee' }};
        }

        /* Striped Tables */
        @if(!empty($doc_settings['doc_table_striped']))
        tr:nth-child(even) {
            background-color: {{ $doc_settings['doc_secondary_color'] ?? '#f9f9f9' }};
        }
        @endif

        /* Bordered Tables */
        @if(!empty($doc_settings['doc_table_bordered']))
        table, th, td {
            border: 1px solid {{ $doc_settings['doc_secondary_color'] ?? '#ddd' }};
        }
        @endif

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Helper Classes */
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-4 { margin-top: 1rem; }
        
        .row::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .col-6 {
            float: left;
            width: 50%;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f3f4f6; color: #1f2937; }

        /* Document Specific */
        .document-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: {{ $doc_settings['doc_primary_color'] ?? '#2563eb' }};
        }

        .meta-box {
            background-color: {{ $doc_settings['doc_secondary_color'] ?? '#f9f9f9' }};
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .meta-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo-container">
                @if(!empty($doc_settings['doc_show_logo']) && !empty($doc_settings['doc_logo']))
                    <img src="{{ storage_path('app/public/' . $doc_settings['doc_logo']) }}" alt="Logo">
                @else
                    <div class="company-name">{{ $doc_settings['doc_company_name'] ?? config('app.name') }}</div>
                @endif
                
                @if(!empty($doc_settings['doc_header_text']))
                    <div style="margin-top: 10px; font-size: 10px;">
                        {!! $doc_settings['doc_header_text'] !!}
                    </div>
                @endif
            </div>
            
            <div class="company-info">
                <div class="company-name">{{ $doc_settings['doc_company_name'] ?? '' }}</div>
                @if(!empty($doc_settings['doc_company_address']))
                    <div>{!! nl2br(e($doc_settings['doc_company_address'])) !!}</div>
                @endif
                @if(!empty($doc_settings['doc_company_phone']))
                    <div>Phone: {{ $doc_settings['doc_company_phone'] }}</div>
                @endif
                @if(!empty($doc_settings['doc_company_email']))
                    <div>Email: {{ $doc_settings['doc_company_email'] }}</div>
                @endif
                @if(!empty($doc_settings['doc_company_website']))
                    <div>{{ $doc_settings['doc_company_website'] }}</div>
                @endif
                @if(!empty($doc_settings['doc_company_tax_id']))
                    <div>Tax ID: {{ $doc_settings['doc_company_tax_id'] }}</div>
                @endif
            </div>
        </div>
    </header>

    <footer>
        @if(!empty($doc_settings['doc_footer_text']))
            {!! $doc_settings['doc_footer_text'] !!}
        @else
            {{ $doc_settings['doc_company_name'] ?? config('app.name') }} 
            @if(!empty($doc_settings['doc_company_website']))
             - {{ $doc_settings['doc_company_website'] }}
            @endif
        @endif
        <script type="text/php">
            if (isset($pdf)) {
                $x = 550;
                $y = 820;
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $font = null;
                $size = 9;
                $color = array(0.5, 0.5, 0.5);
                $word_space = 0.0;  //  default
                $char_space = 0.0;  //  default
                $angle = 0.0;   //  default
                $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            }
        </script>
    </footer>

    <main>
        @yield('content')
    </main>
</body>
</html>
