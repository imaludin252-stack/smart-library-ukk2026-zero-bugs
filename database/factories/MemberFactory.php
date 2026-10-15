<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nisn' => fake()->unique()->numerify('00########'),
            'kelas' => fake()->randomElement(['X RPL 1', 'XI TKJ 2', 'XII AKL 1', 'XII RPL 1', 'XI OTKP 2']),
            'jurusan' => fake()->randomElement(['RPL', 'TKJ', 'AKL', 'OTKP']),
            'status' => 'aktif',
        ];
    }
}
