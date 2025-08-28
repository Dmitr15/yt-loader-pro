<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviewSubtitles extends Model
{
    use HasFactory;

    protected $fillable = [
        'preview_data_id',
        'type',
        'lang_code',
        'lang_name'
    ];

    public function previewData(): BelongsTo
    {
        return $this->belongsTo(PreviewData::class);
    }
}
