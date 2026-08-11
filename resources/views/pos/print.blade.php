<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $receipt['sale_id'] }}</title>
    <style>
        body {
            background-color: #ffffff;
            color: #000000;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt;
            margin: 0;
            padding: 10px;
        }

        .receipt-print-container {
            width: 72mm;
            margin: 0 auto;
        }

        .receipt-copy {
            width: 100%;
            background: #ffffff;
            margin-bottom: 20px;
        }

        .receipt-head {
            text-align: center;
            margin-bottom: 8px;
        }

        .receipt-store-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-address {
            font-size: 8pt;
            color: #333;
            margin-top: 4px;
            line-height: 1.3;
        }

        .receipt-divider {
            color: #000;
            margin: 8px 0;
            letter-spacing: 1px;
            text-align: center;
        }

        .receipt-meta {
            font-size: 8.5pt;
            margin-bottom: 6px;
            line-height: 1.4;
            text-align: left;
        }

        .receipt-items {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 9pt;
        }

        .receipt-items th {
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .receipt-items td {
            padding: 4px 0;
            vertical-align: top;
        }

        .receipt-totals {
            margin: 4px 0;
        }

        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 9.5pt;
        }

        .receipt-grand {
            font-size: 11pt;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 12px;
            font-size: 8.5pt;
            line-height: 1.3;
        }

        .receipt-tear-divider {
            text-align: center;
            border-top: 1.5px dashed #000000;
            margin: 28px 0;
            padding-top: 8px;
            font-size: 8pt;
            font-family: monospace;
            color: #333;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px; padding: 12px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-family: sans-serif;">
        <div style="font-weight: bold; margin-bottom: 8px; font-size: 0.95rem; color: #1e293b;">Receipt Print Preview</div>
        <button onclick="window.print()" style="padding: 8px 18px; font-weight: bold; cursor: pointer; border-radius: 6px; background: #4f46e5; color: white; border: none; font-size: 0.85rem; box-shadow: 0 4px 6px rgba(79,70,229,0.2);">Print Receipt</button>
        <button onclick="window.close()" style="padding: 8px 18px; margin-left: 8px; cursor: pointer; border-radius: 6px; background: #ffffff; color: #334155; border: 1.5px solid #cbd5e1; font-size: 0.85rem; font-weight: 600;">Close Window</button>
    </div>

    <div class="receipt-print-container">
        <!-- Copy 1: Customer Copy -->
        <div class="receipt-copy customer-copy">
            <div class="receipt-head">
                <div class="receipt-store-name">{{ config('app.name') }}</div>
                <div class="receipt-address">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu</div>
                <div style="font-weight: bold; font-size: 9.5pt; margin-top: 8px; text-transform: uppercase;">*** CUSTOMER COPY ***</div>
                <div class="receipt-divider">------------------------------------</div>
                <div class="receipt-meta">
                    <div>Receipt #{{ $receipt['sale_id'] }}</div>
                    <div>Date: {{ $receipt['created_at'] }}</div>
                    <div>Cashier: {{ $receipt['cashier'] }}</div>
                </div>
                <div class="receipt-divider">------------------------------------</div>
            </div>

            <table class="receipt-items">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center; width: 12%;">Qty</th>
                        <th style="text-align:right; width: 22%;">Price</th>
                        <th style="text-align:right; width: 24%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}@if(!empty($item['is_bulk'])) <small>(bulk)</small>@endif</td>
                            <td style="text-align:center;">{{ $item['qty'] }}</td>
                            <td style="text-align:right;">&#8369;{{ number_format($item['price'], 2) }}</td>
                            <td style="text-align:right;">&#8369;{{ number_format($item['price'] * $item['qty'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="receipt-divider">------------------------------------</div>
            <div class="receipt-totals">
                <div class="receipt-total-row receipt-grand"><span>Total Amount</span><strong>&#8369;{{ number_format($receipt['total'], 2) }}</strong></div>
                <div class="receipt-total-row"><span>Payment Mode</span><span>{{ strtoupper($receipt['payment_method']) }}</span></div>
                <div class="receipt-total-row"><span>Cash Tendered</span><span>&#8369;{{ number_format($receipt['cash'], 2) }}</span></div>
                <div class="receipt-total-row"><span>Change</span><span>&#8369;{{ number_format($receipt['change'], 2) }}</span></div>
            </div>
            <div class="receipt-divider">------------------------------------</div>
            <div class="receipt-footer">Thank you for shopping at {{ config('app.name') }}!</div>
        </div>

        <!-- Tear Guide -->
        <div class="receipt-tear-divider">
            ---------------- TEAR HERE ----------------
        </div>

        <!-- Copy 2: Store Copy -->
        <div class="receipt-copy store-copy">
            <div class="receipt-head">
                <div class="receipt-store-name">{{ config('app.name') }}</div>
                <div class="receipt-address">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu</div>
                <div style="font-weight: bold; font-size: 9.5pt; margin-top: 8px; text-transform: uppercase;">*** STORE COPY ***</div>
                <div class="receipt-divider">------------------------------------</div>
                <div class="receipt-meta">
                    <div>Receipt #{{ $receipt['sale_id'] }}</div>
                    <div>Date: {{ $receipt['created_at'] }}</div>
                    <div>Cashier: {{ $receipt['cashier'] }}</div>
                </div>
                <div class="receipt-divider">------------------------------------</div>
            </div>

            <table class="receipt-items">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center; width: 12%;">Qty</th>
                        <th style="text-align:right; width: 22%;">Price</th>
                        <th style="text-align:right; width: 24%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}@if(!empty($item['is_bulk'])) <small>(bulk)</small>@endif</td>
                            <td style="text-align:center;">{{ $item['qty'] }}</td>
                            <td style="text-align:right;">&#8369;{{ number_format($item['price'], 2) }}</td>
                            <td style="text-align:right;">&#8369;{{ number_format($item['price'] * $item['qty'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="receipt-divider">------------------------------------</div>
            <div class="receipt-totals">
                <div class="receipt-total-row receipt-grand"><span>Total Amount</span><strong>&#8369;{{ number_format($receipt['total'], 2) }}</strong></div>
                <div class="receipt-total-row"><span>Payment Mode</span><span>{{ strtoupper($receipt['payment_method']) }}</span></div>
                <div class="receipt-total-row"><span>Cash Tendered</span><span>&#8369;{{ number_format($receipt['cash'], 2) }}</span></div>
                <div class="receipt-total-row"><span>Change</span><span>&#8369;{{ number_format($receipt['change'], 2) }}</span></div>
            </div>
            <div class="receipt-divider">------------------------------------</div>
            <div class="receipt-footer">Thank you for shopping at {{ config('app.name') }}!</div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
