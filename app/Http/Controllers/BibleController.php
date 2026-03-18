<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibleQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BibleController extends Controller
{
    public function index()
    {
        $questions = BibleQuestion::inRandomOrder()
            ->limit(20)
            ->get()
            ->map(function($q){
                return [
                    'id' => $q->id,
                    'book' => $q->book,
                    'chapter' => $q->chapter,
                    'verse' => $q->verse, // ✅ WAJIB (untuk mode lain)
                    'verse_text' => $q->verse_text,
                    'time_limit_seconds' => $q->time_limit_seconds
                ];
            });

        return view('alkitab.index', compact('questions'));
    }

    public function menu()
    {
        return view('alkitab.menu');
    }

    public function multiplayerLobby()
    {
        return view('alkitab.multiplayer-lobby');
    }

    public function multiplayerPlay($code)
    {
        return view('alkitab.multiplayer-play', compact('code'));
    }

    public function checkAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:bible_questions,id',
            'mode' => 'required|in:verse,chapter,book',
            'answer' => 'required'
        ]);

        $question = BibleQuestion::find($request->question_id);

        $correct = false;

        switch ($request->mode) {

            case 'verse':
                if (!is_numeric($request->answer)) break;
                $correct = (int)$request->answer === (int)$question->verse;
                break;

            case 'chapter':
                if (!is_numeric($request->answer)) break;
                $correct = (int)$request->answer === (int)$question->chapter;
                break;

            case 'book':
                $correct = strtolower(trim($request->answer)) === strtolower($question->book);
                break;
        }

        return response()->json([
            'correct' => $correct
        ]);
    }

    public function createRoom(Request $request)
{
    $code = strtoupper(Str::random(5));

    $roomId = DB::table('bible_rooms')->insertGetId([
        'code' => $code,
        'status' => 'waiting'
    ]);

    $playerId = DB::table('bible_players')->insertGetId([
        'room_id' => $roomId,
        'name' => $request->name,
        'score' => 0,
        'is_host' => true
    ]);

    session([
        'player_id' => $playerId,
        'player_name' => $request->name
    ]);

    return redirect("/alkitab/multiplayer/play/$code");
}

public function joinRoom($code)
{
    $room = DB::table('bible_rooms')->where('code', $code)->first();

    if(!$room){
        return redirect()->back()->with('error', 'Room tidak ditemukan');
    }

    $name = request()->name ?? session('player_name') ?? 'Player';

    $playerId = DB::table('bible_players')->insertGetId([
        'room_id' => $room->id,
        'name' => $name,
        'score' => 0
    ]);

    session([
        'player_id' => $playerId,
        'player_name' => $name
    ]);

    return redirect("/alkitab/multiplayer/play/$code");
}

}