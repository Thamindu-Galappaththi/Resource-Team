<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nic' => fake()->unique()->numerify('############'),
            'phone' => '0771234567',
            'location' => 'Welisara',
            'password' => 'password',
            'user_role' => 'admin',
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if ($user->role_id) {
                return;
            }

            $slug = $user->user_role ?: 'admin';
            $role = Role::query()->where('slug', $slug)->first()
                ?? Role::query()->create([
                    'slug' => $slug,
                    'name' => str($slug)->replace('_', ' ')->title(),
                    'is_active' => true,
                ]);

            $user->role_id = $role->id;
            $user->user_role = $role->slug;
        });
    }

    public function role(string $slug): static
    {
        return $this->state(fn () => ['user_role' => $slug]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
