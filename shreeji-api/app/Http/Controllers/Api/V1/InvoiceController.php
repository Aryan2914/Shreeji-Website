<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Download invoice PDF or metadata.
     * GET /api/v1/orders/{orderNumber}/invoice
     */
    public function download(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['invoice', 'user.businessProfile', 'address', 'items'])
            ->firstOrFail();

        if (!$order->invoice && $order->isPaid()) {
            $this->invoiceService->createInvoiceForOrder($order);
            $order->refresh();
        }

        if (!$order->invoice) {
            return response()->json([
                'message' => 'Invoice not yet generated for this order. Invoices are generated once payment is confirmed.',
            ], 404);
        }

        // Return PDF stream directly if format=pdf or accept header is application/pdf
        if ($request->query('format') === 'pdf' || $request->wantsJson() === false) {
            $pdf = Pdf::loadView('invoices.gst_invoice', [
                'invoice' => $order->invoice,
                'order' => $order,
            ]);

            return $pdf->download($order->invoice->invoice_number . '.pdf');
        }

        return response()->json([
            'data' => [
                'invoice_number' => $order->invoice->invoice_number,
                'invoice_date' => $order->invoice->invoice_date,
                'download_url' => url('/api/v1/orders/' . $orderNumber . '/invoice?format=pdf'),
                'total_amount' => $order->invoice->total_amount,
                'cgst_amount' => $order->invoice->cgst_amount,
                'sgst_amount' => $order->invoice->sgst_amount,
                'igst_amount' => $order->invoice->igst_amount,
                'is_intra_state' => $order->invoice->isIntraState(),
            ],
        ]);
    }
}
