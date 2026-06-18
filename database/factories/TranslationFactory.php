<?php

namespace Database\Factories;

use App\Enums\TranslationContext;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(3).'.'.$this->faker->word(),
            'description' => $this->faker->optional()->sentence(),
            'tags' => $this->faker->randomElements(
                array_column(TranslationContext::cases(), 'value'),
                $this->faker->numberBetween(1, 3),
            ),
        ];
    }
}
