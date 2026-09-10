<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'branch',
        'instrument',
        'level_reached',
        'pct',
        'severity',
    ];

    protected $casts = [
        'level_reached' => 'integer',
        'pct' => 'integer',
    ];

    /**
     * Get the video associated with this result.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}