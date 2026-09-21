<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Get the next atomic sequential number for a given sequence name.
     * Uses row locking to prevent race conditions during concurrent orders/invoices.
     */
    public static function next(string $name): int
    {
        return DB::transaction(function () use ($name) {
            $row = DB::table('number_sequences')
                ->where('name', $name)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                // Ensure initial insert
                try {
                    DB::table('number_sequences')->insert([
                        'name' => $name,
                        'current_value' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return 1;
                } catch (\Throwable) {
                    // Handled if concurrent insert occurred
                    $row = DB::table('number_sequences')
                        ->where('name', $name)
                        ->lockForUpdate()
                        ->first();
                }
            }

            $next = ($row ? $row->current_value : 0) + 1;

            DB::table('number_sequences')
                ->where('name', $name)
                ->update([
                    'current_value' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        });
    }
}
