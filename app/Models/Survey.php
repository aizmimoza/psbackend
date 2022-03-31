<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Survey extends Model {
    use HasFactory, HasSlug;

    const QUESTION_INPUT_TEXT = 'text';
    const QUESTION_INPUT_SELECT = 'select';
    const QUESTION_INPUT_RADIO = 'radio';
    const QUESTION_INPUT_CHECKBOX = 'checkbox';
    const QUESTION_INPUT_TEXTAREA = 'textarea';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'status',
        'image',
        'description',
        'expire_date',
        // 'created_at',
        // 'updated_at',
    ];

    // use 'title' attribute to generate 'slug'
    public function getSlugOptions() : SlugOptions {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    // the name of function is inportant
    public function questions() {
        return $this->hasMany(SurveyQuestion::class);
    }

    // the name of function is inportant
    public function answers() {
        return $this->hasMany(SurveyAnswer::class);
    }
}
