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
            ->limit(100)
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
    $request->validate([
        'name' => 'required|string|max:50',
        'max_players' => 'required|integer|min:2|max:4'
    ]);

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
    // ✅ DI SINI
    $request->validate([
        'name' => 'required|string|max:50',
        'code' => 'required|string'
    ]);

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

        // ✅ TARUH DI SINI (WAJIB)
    if (!$room) {
        return response()->json(['error' => 'Room tidak ditemukan']);
    }

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

    if (!$room) {
    return response()->json(['error' => 'Room tidak ditemukan']);
}

    $playerId = session('player_id');

    $player = DB::table('bible_players')
        ->where('room_id', $room->id)
        ->where('id', $playerId)
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

    // START GAME
    DB::table('bible_rooms')
        ->where('id', $room->id)
        ->update([
            'status' => 'playing',
            'start_time' => now(),
            'end_time' => now()->addMinutes(5)
        ]);

    // INSERT SOAL PERTAMA
    $q = DB::table('bible_questions')->inRandomOrder()->first();

    DB::table('bible_multiplayer_questions')->insert([
        'id' => Str::uuid(),
        'room_id' => $room->id,
        'question_id' => $q->id,
        'book' => $q->book,
        'chapter' => $q->chapter,
        'verse' => $q->verse,
        'verse_text' => $q->verse_text,
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

    // ✅ TIMER SOAL
    $timeLeft = 0;
    if ($question && $question->created_at) {
        $elapsed = now()->diffInSeconds(Carbon::parse($question->created_at));
        $timeLeft = max(60 - $elapsed, 0);
    }

    // 🔥 ⬇️ TARUH DI SINI (SETELAH TIMER)
    if ($question && $timeLeft <= 0 && !$question->answered_by) {

        // ✅ AKHIRI SOAL
        DB::table('bible_multiplayer_questions')
            ->where('id', $question->id)
            ->update([
                'ended_at' => now()
            ]);

        // 🔢 HITUNG SOAL
        $count = DB::table('bible_multiplayer_questions')
            ->where('room_id', $room->id)
            ->count();

        if ($count >= 20) {

            DB::table('bible_rooms')
                ->where('id', $room->id)
                ->update(['status' => 'finished']);

            $room->status = 'finished';

        } else {

            $used = DB::table('bible_multiplayer_questions')
                ->where('room_id', $room->id)
                ->pluck('question_id');

            $next = DB::table('bible_questions')
                ->whereNotIn('id', $used)
                ->inRandomOrder()
                ->first();

            if ($next) {
                DB::table('bible_multiplayer_questions')->insert([
                    'id' => Str::uuid(),
                    'room_id' => $room->id,
                    'question_id' => $next->id,
                    'book' => $next->book,
                    'chapter' => $next->chapter,
                    'verse' => $next->verse,
                    'verse_text' => $next->verse_text,
                    'created_at' => now()
                ]);
            } else {
                DB::table('bible_rooms')
                    ->where('id', $room->id)
                    ->update(['status' => 'finished']);

                $room->status = 'finished';
            }
        }

        // 🔥 REFRESH QUESTION BARU (WAJIB)
        $question = DB::table('bible_multiplayer_questions')
            ->where('room_id', $room->id)
            ->whereNull('ended_at')
            ->latest()
            ->first();

        // reset timer
        if ($question && $question->created_at) {
            $elapsed = now()->diffInSeconds(Carbon::parse($question->created_at));
            $timeLeft = max(60 - $elapsed, 0);
        }
    }

    // ✅ TIMER SESSION
    $sessionLeft = 0;
    if ($room->end_time) {
        $sessionLeft = max(now()->diffInSeconds($room->end_time, false), 0);
    }

    // 🏁 AUTO FINISH (TIME)
    if ($sessionLeft <= 0 && $room->status === 'playing') {
        DB::table('bible_rooms')
            ->where('id', $room->id)
            ->where('status', 'playing')
            ->update(['status' => 'finished']);

        $room->status = 'finished';
    }

    return response()->json([
        'room_status' => $room->status,
        'players' => $players,
        'question' => $question,
        'time_left' => $timeLeft,
        'session_left' => $sessionLeft,
        'answered_by' => $question ? $question->answered_by : null
    ]);
}

public function answerMultiplayer(Request $request)
{
    // ✅ 1. VALIDASI INPUT
    $request->validate([
        'room_code' => 'required|string',
        'answer' => 'required'
    ]);

    // ✅ 2. AMBIL ROOM
    $room = DB::table('bible_rooms')
        ->where('code', $request->room_code)
        ->first();

    if (!$room || $room->status !== 'playing') {
        return response()->json(['error' => 'Game tidak aktif']);
    }

    $playerId = session('player_id');

    // ✅ 3. VALIDASI PLAYER
    $player = DB::table('bible_players')
        ->where('id', $playerId)
        ->where('room_id', $room->id)
        ->first();

    if (!$player) {
        return response()->json(['error' => 'Player tidak valid']);
    }

    DB::beginTransaction();

    try {

        // 🔒 4. LOCK QUESTION (ANTI RACE CONDITION)
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

        // ❗ SUDAH ADA PEMENANG
        if ($question->answered_by) {
            DB::rollBack();
            return response()->json(['correct' => false]);
        }

        // ⏱ 5. CEK TIMEOUT (ANTI CHEAT)
        $elapsed = now()->diffInSeconds($question->created_at);

        if ($elapsed > 20) {
            DB::rollBack();
            return response()->json(['error' => 'Waktu habis']);
        }

        // ✅ 6. VALIDASI JAWABAN
        $correct = (int)$request->answer === (int)$question->verse;

        // 📝 7. SIMPAN JAWABAN
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

            // 🏆 SET WINNER
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

            // 🔢 LIMIT SOAL (MAX 20)
            $count = DB::table('bible_multiplayer_questions')
                ->where('room_id', $room->id)
                ->count();

            if ($count >= 20) {

                DB::table('bible_rooms')
                    ->where('id', $room->id)
                    ->update(['status' => 'finished']);

            } else {

                // ❌ AVOID DUPLIKAT
                $used = DB::table('bible_multiplayer_questions')
                    ->where('room_id', $room->id)
                    ->pluck('question_id');

                $next = DB::table('bible_questions')
                    ->whereNotIn('id', $used)
                    ->inRandomOrder()
                    ->first();

                if ($next) {

                    DB::table('bible_multiplayer_questions')->insert([
                        'id' => Str::uuid(),
                        'room_id' => $room->id,
                        'question_id' => $next->id,
                        'book' => $next->book,
                        'chapter' => $next->chapter,
                        'verse' => $next->verse,
                        'verse_text' => $next->verse_text,
                        'created_at' => now()
                    ]);

                } else {

                    // 🏁 HABIS SOAL
                    DB::table('bible_rooms')
                        ->where('id', $room->id)
                        ->update(['status' => 'finished']);
                }
            }
        }

        DB::commit();

        return response()->json(['correct' => $correct]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json(['error' => 'Server error']);
    }
}

}