<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'hls_master_path',
        'preview_clip_path',
        'download_file_path',
        'renditions_json',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'renditions_json' => 'array',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
