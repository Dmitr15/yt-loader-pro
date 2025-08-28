<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreviewData extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'status',
        'title',
        'thumbnail',
        'error_message',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function videoFormats(): HasMany
    {
        return $this->hasMany(PreviewVideoFormats::class);
    }

    public function audioFormats(): HasMany
    {
        return $this->hasMany(PreviewAudioFormats::class);
    }

    public function subtitles(): HasMany
    {
        return $this->hasMany(PreviewSubtitles::class);
    }
}
