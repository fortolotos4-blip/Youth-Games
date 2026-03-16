<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongLyric extends Model
{
    protected $table = 'song_lyrics';

    protected $fillable = [
        'lyric'
    ];

    public $timestamps = true;
}