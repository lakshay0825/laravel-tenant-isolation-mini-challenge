<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ServiceLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceLog>
 */
class ServiceLogFactory extends Factory
{
    protected $model = ServiceLog::class;

    public function definition(): array
    {
        $crpId = (string) Str::uuid();

        return [
            'crp_id' => $crpId,
            'client_id' => Client::factory()->state(['crp_id' => $crpId]),
            'service_type' => fake()->randomElement(['consultation', 'follow_up', 'intake']),
            'notes' => fake()->sentence(),
            'document_path' => null,
        ];
    }
}
