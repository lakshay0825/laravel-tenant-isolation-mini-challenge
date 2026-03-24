<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'crp_id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'ssn' => fake()->numerify('###-##-####'),
            'dob' => fake()->date(),
            'signature_path' => null,
        ];
    }
}
