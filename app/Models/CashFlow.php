<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashFlow extends Model
{
    /** @use HasFactory<\Database\Factories\CashFlowFactory> */
    use HasFactory;

    protected $fillable = ['type', 'category', 'amount', 'description', 'reference_type', 'reference_id', 'transaction_date'];

    protected static function booted()
    {
        // AMUNISI ANTI-ERROR: Intersept data sebelum masuk ke MySQL
        static::creating(function ($cashFlow) {
            // Kalau kasir gak ngisi tipe (dari form pengeluaran), paksa set jadi 'Expense'
            if (empty($cashFlow->type)) {
                $cashFlow->type = 'Expense';
            }
            
            // Kalau kasir gak ngisi kategori, paksa set jadi 'Operational' biar lolos dari validasi MySQL lu
            if (empty($cashFlow->category)) {
                $cashFlow->category = 'Operational';
            }
        });
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
