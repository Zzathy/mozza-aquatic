<?php

namespace Database\Factories;

use App\Models\StockEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockEntry>
 */
class StockEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = StockEntry::class;

    public function definition(): array
    {
        $suppliers = [
            'Mas Ndut Tulungagung', 'Agen Ikan Hias Kediri', 'Distributor Pelet Blitar', 
            'Mozza Aquatic Pusat', 'Supplier Tanaman Air Malang'
        ];

        return [
            'supplier_name' => $this->faker->randomElement($suppliers),
            'supplier_phone' => $this->faker->phoneNumber(),
            'supplier_address' => $this->faker->address(),
            'entry_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => $this->faker->sentence(),
            // Kolom keuangan kita set default 0 dulu, karena angkanya bakal dihitung otomatis dari factory batche-nya nanti!
            'total_amount' => 0,
            'discount' => 0,
            'final_amount' => 0,
            'payment_status' => 'Lunas',
            'paid_amount' => 0,
            'due_amount' => 0,
        ];
    }
}
