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

    public function multiplayerMenu()
{
    return view('alkitab.multiplayer-menu');
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
        'status' => 'waiting',
        'max_players' => $request->max_players,
        'created_at' => now()
    ]);

    $playerId = DB::table('bible_players')->insertGetId([
        'room_id' => $roomId,
        'name' => $request->name,
        'score' => 0,
        'is_host' => true,
        'created_at' => now()
    ]);

    session([
        'player_id' => $playerId,
        'player_name' => $request->name
    ]);

    // 🔥 FIX: ke lobby, bukan play
    return response()->json([
        'room_code' => $code
    ]);
}

public function joinRoom(Request $request)
{
    $room = DB::table('bible_rooms')
        ->where('code', $request->code)
        ->first();

    if (!$room) {
        return response()->json(['error' => 'Room tidak ditemukan']);
    }

    // 🔥 VALIDASI WAJIB
    $playerCount = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->count();

    if ($room->status !== 'waiting') {
        return response()->json(['error' => 'Game sudah dimulai']);
    }

    if ($playerCount >= $room->max_players) {
        return response()->json(['error' => 'Room penuh']);
    }

    $playerId = DB::table('bible_players')->insertGetId([
        'room_id' => $room->id,
        'name' => $request->name,
        'score' => 0,
        'is_host' => false,
        'created_at' => now()
    ]);

    session([
        'player_id' => $playerId,
        'player_name' => $request->name
    ]);

    return response()->json([
        'success' => true
    ]);
}

public function lobby($code)
{
    return view('alkitab.multiplayer-lobby', [
        'roomCode' => $code
    ]);
}

public function state($code)
{
    $room = DB::table('bible_rooms')
        ->where('code', $code)
        ->first();

    $players = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->select('id', 'name', 'is_host')
        ->get();

    return response()->json([
        'room' => [
            'code' => $room->code,
            'status' => $room->status,
            'max_players' => $room->max_players,
            'start_time' => $room->start_time
        ],
        'players' => $players
    ]);
}

public function startGame($code, Request $request)
{
    $room = DB::table('bible_rooms')->where('code', $code)->first();

    $player = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->where('name', $request->player_name)
        ->first();

    if (!$player || !$player->is_host) {
        return response()->json(['error' => 'Hanya host']);
    }

    $count = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->count();

    if ($count < 2) {
        return response()->json(['error' => 'Minimal 2 pemain']);
    }

    // ✅ update room
    DB::table('bible_rooms')
        ->where('id', $room->id)
        ->update([
            'status' => 'playing',
            'start_time' => now()
        ]);

    // ✅ INSERT SOAL PERTAMA
    $q = DB::table('bible_questions')->inRandomOrder()->first();

    DB::table('bible_multiplayer_questions')->insert([
        'id' => Str::uuid(),
        'room_id' => $room->id,
        'question_id' => $q->id,
        'book' => $q->book,
        'chapter' => $q->chapter,
        'verse' => $q->verse,
        'verse_text' => $q->verse_text,
        'answered_by' => null,
        'ended_at' => null,
        'created_at' => now()
    ]);

    return response()->json(['success' => true]);
}

public function gameState($code)
{
    $room = DB::table('bible_rooms')->where('code', $code)->first();

    if (!$room) {
        return response()->json(['error' => 'Room not found']);
    }

    $players = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->select('id', 'name', 'score')
        ->get();

    $question = DB::table('bible_multiplayer_questions')
        ->where('room_id', $room->id)
        ->whereNull('ended_at')
        ->latest()
        ->first();

    // ✅ TIMER PAKAI ROOM
    $timeLeft = 0;

    if ($room->start_time) {
        $elapsed = now()->diffInSeconds($room->start_time);
        $timeLeft = max(20 - $elapsed, 0);
    }

    return response()->json([
        'room_status' => $room->status,
        'players' => $players,
        'question' => $question,
        'time_left' => $timeLeft,
        'answered_by' => $question->answered_by ?? null
    ]);
}

public function answerMultiplayer(Request $request)
{
    $room = DB::table('bible_rooms')
        ->where('code', $request->room_code)
        ->first();

    $playerId = $request->player_id;

    DB::beginTransaction();

    try {

        // 🔒 LOCK QUESTION
        $question = DB::table('bible_multiplayer_questions')
            ->where('room_id', $room->id)
            ->whereNull('ended_at')
            ->latest()
            ->lockForUpdate()
            ->first();

        if (!$question) {
            DB::rollBack();
            return response()->json(['error' => 'No active question']);
        }

        // ❗ SUDAH ADA YANG MENANG
        if ($question->answered_by) {
            DB::rollBack();
            return response()->json(['correct' => false]);
        }

        // 🔍 VALIDASI JAWABAN
        $correct = (int)$request->answer === (int)$question->verse;

        // 📝 SIMPAN JAWABAN
        DB::table('bible_answers')->insert([
            'id' => Str::uuid(),
            'room_id' => $room->id,
            'player_id' => $playerId,
            'question_id' => $question->question_id,
            'answer' => $request->answer,
            'is_correct' => $correct,
            'created_at' => now()
        ]);

        if ($correct) {

            // 🏆 SET PEMENANG
            DB::table('bible_multiplayer_questions')
                ->where('id', $question->id)
                ->update([
                    'answered_by' => $playerId,
                    'ended_at' => now()
                ]);

            // ➕ SCORE
            DB::table('bible_players')
                ->where('id', $playerId)
                ->increment('score', 10);

            // ✅ NEXT QUESTION (WAJIB DI SINI)
            $next = DB::table('bible_questions')
                ->inRandomOrder()
                ->first();

            DB::table('bible_multiplayer_questions')->insert([
                'id' => Str::uuid(),
                'room_id' => $room->id,
                'question_id' => $next->id,
                'book' => $next->book,
                'chapter' => $next->chapter,
                'verse' => $next->verse,
                'verse_text' => $next->verse_text,
                'answered_by' => null,
                'ended_at' => null,
                'created_at' => now()
            ]);

            // ✅ RESET TIMER
            DB::table('bible_rooms')
                ->where('id', $room->id)
                ->update([
                    'start_time' => now()
                ]);
        }

        DB::commit();

        return response()->json([
            'correct' => $correct
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'error' => 'Server error'
        ]);
    }
}

}