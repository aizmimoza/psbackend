<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model {
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'start_date',
        'end_date',
    ];

    // the name of function is inportant
    public function survey() {
        return $this->belongsTo(Survey::class);
    }
}
