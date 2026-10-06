<?php

namespace Database\Factories;

use App\Models\Intern;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternFactory extends Factory
{
    protected $model = Intern::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'nim' => fake()->unique()->numerify('NIM-######'),
            'institution' => fake()->company(),
            'major' => 'Teknik Informatika',
            'phone' => fake()->phoneNumber(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'registered_via' => 'admin',
        ];
    }
}
