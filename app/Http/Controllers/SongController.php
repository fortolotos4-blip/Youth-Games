<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SongLyric;

class SongController extends Controller
{

    public function menu()
    {
        return view('song.menu');
    }

    public function index()
    {
        return view('song.single');
    }

    public function team()
    {
        return view('song.team');
    }

    public function random(Request $request)
{

    $used = $request->input('used', []);

    $song = SongLyric::whereNotIn('id', $used)
                ->inRandomOrder()
                ->first();

    if(!$song){

        $song = SongLyric::inRandomOrder()->first();

        return response()->json([
            'reset' => true,
            'id' => $song->id,
            'lyric' => $song->lyric
        ]);

    }

    return response()->json([
        'id' => $song->id,
        'lyric' => $song->lyric
    ]);
}


    public function record(Request $request)
    {
        return response()->json([
            'status'=>'success'
        ]);
    }

}