<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ServiceLog;
use App\Models\User;
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
            'staff_id' => User::factory(),
            'goal_id' => null,
            'notes_master' => ['narrative' => fake()->sentence()],
            'narrative_hash' => null,
            'billing_status' => 'pending',
            'invoice_number' => null,
            'locked_at' => null,
        ];
    }
}
