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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
