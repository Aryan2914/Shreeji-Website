<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-bottom: 2px solid #ea580c; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 20px; font-weight: bold; color: #ea580c; text-align: right; }
        .subtitle { font-size: 10px; color: #666; text-align: right; }
        .party-table { width: 100%; margin-bottom: 15px; }
        .party-box { width: 48%; vertical-align: top; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px; font-size: 10px; text-transform: uppercase; text-align: left; }
        .items-table td { border: 1px solid #cbd5e1; padding: 6px; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-bottom: 20px; }
        .totals-table td { padding: 4px 8px; border: 1px solid #cbd5e1; }
        .footer-note { font-size: 9px; color: #64748b; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h2 style="margin: 0; color: #1e293b; font-size: 18px;">SHREEJI INFOTECH</h2>
                <div style="font-size: 10px; color: #475569;">
                    Electronics, Networking & Industrial Components Showroom<br>
                    Maninagar, Ahmedabad, Gujarat - 380008<br>
                    <strong>GSTIN:</strong> {{ $invoice->seller_gstin ?? '24AABCS1234F1Z5' }} | <strong>State:</strong> Gujarat (Code: 24)<br>
                    <strong>Email:</strong> sales@shreejiinfo.in | <strong>Phone:</strong> +91 9377704344
                </div>
            </td>
            <td class="text-right">
                <div class="title">TAX INVOICE</div>
                <div class="subtitle">(Original for Recipient)</div>
                <div style="margin-top: 8px; font-size: 11px;">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Date:</strong> {{ $invoice->invoice_date->format('d-m-Y') }}<br>
                    <strong>Order Ref:</strong> {{ $order->order_number }}
                </div>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td class="party-box">
                <strong style="color: #ea580c;">BILL TO:</strong><br>
                <strong>{{ $order->user->name }}</strong><br>
                @if($order->user->businessProfile)
                    <strong>{{ $order->user->businessProfile->company_name }}</strong><br>
                    <strong>GSTIN:</strong> {{ $order->user->businessProfile->gstin }}<br>
                @endif
                {{ $order->address->address_line_1 }}<br>
                @if($order->address->address_line_2) {{ $order->address->address_line_2 }}<br> @endif
                {{ $order->address->city }}, {{ $order->address->state }} - {{ $order->address->pincode }}<br>
                <strong>Phone:</strong> {{ $order->address->contact_phone }}
            </td>
            <td style="width: 4%;"></td>
            <td class="party-box">
                <strong style="color: #ea580c;">SHIP TO:</strong><br>
                <strong>{{ $order->address->contact_name }}</strong><br>
                {{ $order->address->address_line_1 }}<br>
                @if($order->address->address_line_2) {{ $order->address->address_line_2 }}<br> @endif
                {{ $order->address->city }}, {{ $order->address->state }} - {{ $order->address->pincode }}<br>
                <strong>Phone:</strong> {{ $order->address->contact_phone }}<br>
                <strong>Place of Supply:</strong> {{ $order->address->state }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 40%;">Description of Goods</th>
                <th class="text-center" style="width: 12%;">HSN/SAC</th>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th class="text-right" style="width: 15%;">Unit Rate (₹)</th>
                <th class="text-right" style="width: 20%;">Taxable Value (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    <div style="font-size: 9px; color: #64748b;">SKU: {{ $item->sku_code }}</div>
                </td>
                <td class="text-center">{{ $item->hsn_code ?? '8473' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td><strong>Taxable Amount</strong></td>
            <td class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->isIntraState())
        <tr>
            <td>CGST (9%)</td>
            <td class="text-right">₹{{ number_format($invoice->cgst_amount, 2) }}</td>
        </tr>
        <tr>
            <td>SGST (9%)</td>
            <td class="text-right">₹{{ number_format($invoice->sgst_amount, 2) }}</td>
        </tr>
        @else
        <tr>
            <td>IGST (18%)</td>
            <td class="text-right">₹{{ number_format($invoice->igst_amount, 2) }}</td>
        </tr>
        @endif
        @if($order->shipping_amount > 0)
        <tr>
            <td>Shipping Charges</td>
            <td class="text-right">₹{{ number_format($order->shipping_amount, 2) }}</td>
        </tr>
        @endif
        <tr style="background: #f8fafc; font-weight: bold; font-size: 12px; color: #ea580c;">
            <td>Total (INR)</td>
            <td class="text-right">₹{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <div class="footer-note">
        <strong>Terms & Conditions:</strong><br>
        1. Goods once sold will only be accepted for return as per Shreeji Infotech return policy.<br>
        2. All disputes subject to Ahmedabad jurisdiction.<br>
        3. This is a computer-generated GST tax invoice and does not require physical signature.
    </div>
</body>
</html>
