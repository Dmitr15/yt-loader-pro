<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviewVideoFormats extends Model
{
    use HasFactory;

    protected $fillable = [
        'preview_data_id',
        'format_id',
        'ext',
        'filesize',
        'codec',
        'fps',
        'tbr',
        'vbr',
        'asr',
        'dynamic_range',
        'resolution',
        'format_note'
    ];

    public function previewData(): BelongsTo
    {
        return $this->belongsTo(PreviewData::class);
    }
}
