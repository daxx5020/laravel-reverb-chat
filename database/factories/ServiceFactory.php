<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::where('role', 'seller')->inRandomOrder()->first(), // Creates a new User for each Service if not provided
            'name' => $this->faker->words(2, true), // Random service name
            'description' => $this->faker->paragraph, // Random description
            'price' => $this->faker->randomFloat(2, 10, 500), // Random price between 10 and 500
        ];
    }
}
