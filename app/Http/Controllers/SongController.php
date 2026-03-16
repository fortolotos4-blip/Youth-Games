<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SongLyric;



class SongController extends Controller
{

    public function index()
    {
        return view('song.single');
    }


public function random(Request $request)
{
    $used = $request->used ?? [];

    $song = SongLyric::whereNotIn('id',$used)
            ->inRandomOrder()
            ->first();

    return response()->json([
        'id'=>$song->id,
        'lyric'=>$song->lyric
    ]);
}


    public function record(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Game selesai'
        ]);
    }

}