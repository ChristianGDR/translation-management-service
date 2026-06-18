<?php

namespace App\Models;

use App\Enums\TranslationContext;
use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'description', 'tags'])]
class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => AsEnumCollection::class.':'.TranslationContext::class,
        ];
    }

    /**
     * Per-locale values for this translation key.
     *
     * @return HasMany<TranslationLocale, $this>
     */
    public function locales(): HasMany
    {
        return $this->hasMany(TranslationLocale::class);
    }
}
