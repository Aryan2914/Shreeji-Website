<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'seller_gstin',
        'buyer_gstin',
        'subtotal',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'pdf_url',
        'is_cancelled',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_cancelled' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Determine if this is an intra-state (Gujarat → Gujarat) transaction.
     * Intra-state uses CGST + SGST; inter-state uses IGST.
     */
    public function isIntraState(): bool
    {
        return $this->cgst_amount > 0 && $this->sgst_amount > 0;
    }

    /**
     * Total tax (CGST + SGST or IGST).
     */
    public function getTotalTaxAttribute(): float
    {
        if ($this->isIntraState()) {
            return (float) $this->cgst_amount + (float) $this->sgst_amount;
        }

        return (float) $this->igst_amount;
    }

    /**
     * Generate next sequential invoice number.
     * Format: SI/FY/NNNN (e.g. SI/24-25/0001)
     * Follows the Indian financial year (April to March) with a locked counter, never restarting mid-year.
     */
    public static function generateInvoiceNumber(): string
    {
        $now = now();
        // Indian financial year: Apr–Mar
        $fyStart = $now->month >= 4 ? $now->year : $now->year - 1;
        $fyEnd = $fyStart + 1;
        $fyShort = substr($fyStart, -2) . '-' . substr($fyEnd, -2);

        $prefix = "SI/{$fyShort}/";
        $seq = \App\Services\SequenceService::next('invoice_' . $fyShort);

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
