<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBatch>
 */
class ProductBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = ProductBatch::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(10, 100);
        $price = $this->faker->randomElement([2000, 5000, 12000, 25000, 40000]);

        return [
            'stock_entry_id' => StockEntry::factory(), // Otomatis nempel ke nota induk
            'product_id' => Product::inRandomOrder()->first()->id ?? 1,
            'batch_number' => 'BATCH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'initial_qty' => $qty,
            'remaining_qty' => $qty, // Disamakan karena barang baru masuk kulakan
            'buy_price' => $price,
            'expired_date' => null,
        ];
    }
}
