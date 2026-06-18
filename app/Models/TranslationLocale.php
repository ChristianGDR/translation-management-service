<?php

namespace App\Models;

use Database\Factories\TranslationLocaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['translation_id', 'locale', 'content'])]
class TranslationLocale extends Model
{
    /** @use HasFactory<TranslationLocaleFactory> */
    use HasFactory;

    /**
     * Parent translation key.
     *
     * @return BelongsTo<Translation, $this>
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }
}
