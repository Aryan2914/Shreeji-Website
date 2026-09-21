<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Create or retrieve GST tax invoice for an order.
     * Guaranteed safe against duplicate invoices via database unique constraint and firstOrCreate.
     */
    public function createInvoiceForOrder(Order $order): Invoice
    {
        if ($order->invoice) {
            return $order->invoice;
        }

        $isGujarat = strtolower($order->address->state ?? 'Gujarat') === 'gujarat';
        $subtotal = (float) $order->subtotal;
        $taxAmount = (float) $order->tax_amount;

        $cgst = $isGujarat ? round($taxAmount / 2, 2) : 0;
        $sgst = $isGujarat ? round($taxAmount / 2, 2) : 0;
        $igst = $isGujarat ? 0 : $taxAmount;

        $invoice = Invoice::firstOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now(),
                'seller_gstin' => config('shreeji.company.gstin', '24AABCS1234F1Z5'),
                'buyer_gstin' => $order->user->businessProfile?->gstin,
                'subtotal' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'total_amount' => (float) $order->total_amount,
                'is_cancelled' => false,
            ]
        );

        $this->generatePdf($invoice);

        return $invoice->fresh();
    }

    /**
     * Generate and store PDF file for invoice.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $order = $invoice->order()->with(['user.businessProfile', 'address', 'items'])->first();

        $pdf = Pdf::loadView('invoices.gst_invoice', [
            'invoice' => $invoice,
            'order' => $order,
        ]);

        $filename = 'invoices/' . str_replace('/', '_', $invoice->invoice_number) . '.pdf';
        Storage::disk('local')->put('public/' . $filename, $pdf->output());

        $invoice->update(['pdf_url' => '/storage/' . $filename]);

        return $filename;
    }
}
