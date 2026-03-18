<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BibleQuestion extends Model
{
    protected $table = 'bible_questions';

    protected $fillable = [
        'book',
        'chapter',
        'verse',
        'verse_text',
        'time_limit_seconds'
    ];
}