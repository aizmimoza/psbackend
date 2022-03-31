<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model {
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'type',
        'question',
        'description',
        'data',
        // 'created_at',
        // 'updated_at',
    ];

    // the name of function is inportant
    public function survey() {
        return $this->belongsTo(Survey::class);
    }
}
