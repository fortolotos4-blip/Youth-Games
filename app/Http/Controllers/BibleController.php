<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibleQuestion;

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
}