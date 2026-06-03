<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->text('supplier_address')->nullable(); 
            $table->date('entry_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0); 
            $table->decimal('discount', 15, 2)->default(0);     
            $table->decimal('final_amount', 15, 2)->default(0); 
            $table->string('payment_status')->default('Lunas');
            $table->decimal('paid_amount', 15, 2)->default(0);  
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
    }
};
