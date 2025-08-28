<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviewAudioFormats extends Model
{
    use HasFactory;

    protected $fillable = [
        'preview_data_id',
        'format_id',
        'ext',
        'filesize',
        'lang',
        'codec',
        'abr',
        'tbr',
        'asr'
    ];

    public function previewData(): BelongsTo
    {
        return $this->belongsTo(PreviewData::class);
    }
}
