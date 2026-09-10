<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbsAssessment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'reports' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
