<?php

use Illuminate\Database\Eloquent\Model;

class SongLyric extends Model
{

    protected $table = 'song_lyrics';

    protected $fillable = [
        'lyric'
    ];

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

}