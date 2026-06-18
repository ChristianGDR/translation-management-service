<?php

namespace Database\Factories;

use App\Models\Translation;
use App\Models\TranslationLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationLocale>
 */
class TranslationLocaleFactory extends Factory
{
    protected $model = TranslationLocale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'translation_id' => Translation::factory(),
            'locale' => $this->faker->randomElement(['en', 'fr', 'es']),
            'content' => $this->faker->sentence(),
        ];
    }
}
