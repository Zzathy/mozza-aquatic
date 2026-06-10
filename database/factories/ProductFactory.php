<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'Glowfish Red', 'Glowfish Green', 'Cupang Halfmoon', 
            'Guppy Blue Grass', 'Sakura 100gr', 'Takari 250gr',
            'Obat Biru / Methylene Blue', 'Amara AA-1200', 'LED Kandila P-400',
            'Anubias Nana', 'Pasir Malang Hitam 1kg'
        ];

        $name = $this->faker->unique()->randomElement($names);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => Category::inRandomOrder()->first()->id ?? 1,
            'sku' => 'SKU-' . $this->faker->unique()->numberBetween(10000, 99999),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomElement([5000, 10000, 15000, 25000, 50000, 125000]),
            'min_stock' => $this->faker->randomElement([5, 10, 15]),
            'is_active' => true,
        ];
    }
}
