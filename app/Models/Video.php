<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'audio_path',
        'thumbnail_path',
        'status',
        'model_scores',
    ];

    protected $casts = [
        'model_scores' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function kbsAssessments(): HasOne
    {
        return $this->hasOne(KbsAssessment::class);
    }

    /**
     * Get the individual branch results for this video's KBS assessment.
     */
    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }
}