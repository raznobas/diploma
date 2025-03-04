<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Client::class;
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName,
            'surname' => $this->faker->lastName,
            'patronymic' => $this->faker->firstNameMale,
            'birthdate' => $this->faker->date(),
            'workplace' => $this->faker->company,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'telegram' => '@' . $this->faker->userName,
            'instagram' => '@' . $this->faker->userName,
            'address' => $this->faker->address,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'ad_source' => $this->faker->randomElement(['Google', 'Facebook', 'Instagram', 'Telegram', 'Другое']),
            'is_lead' => $this->faker->boolean,
            'director_id' => null,
        ];
    }

    /**
     * Устанавливает director_id в переданное значение.
     *
     * @param int $directorId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withDirectorId($directorId)
    {
        return $this->state(function (array $attributes) use ($directorId) {
            return [
                'director_id' => $directorId,
            ];
        });
    }
}
