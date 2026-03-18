@extends('layouts.app')

@section('content')
<div x-data="multiplayerGame()" x-init="init()"
     class="min-h-screen bg-gray-100 flex justify-center pt-10 sm:pt-14">

  <div class="w-full max-w-3xl p-4">

    <!-- ROOM -->
    <div class="text-center mb-4">
      <h2 class="text-lg font-bold">
        Room: <span class="text-indigo-600">{{ $code }}</span>
      </h2>
    </div>

    <!-- START BUTTON -->
    <div class="text-center mb-3">
      <button @click="startGame()"
        class="px-4 py-2 bg-green-600 text-white rounded">
        Start Game
      </button>
    </div>

    <!-- PLAYER LIST -->
    <div class="flex gap-2 overflow-x-auto mb-4">
      <template x-for="p in players" :key="p.id">
        <div class="min-w-[90px] bg-white rounded-lg p-2 text-center shadow"
             :class="p.id === lastWinner ? 'ring-2 ring-green-500' : ''">

          <div class="text-sm font-semibold truncate" x-text="p.name"></div>
          <div class="text-lg font-bold text-indigo-600" x-text="p.score"></div>

        </div>
      </template>
    </div>

    <!-- MAIN CARD -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-lg">

      <!-- TIMER + STATUS -->
      <div class="flex justify-between text-sm mb-3">
        <div>⏱ <b x-text="timeLeft"></b>s</div>
        <div class="font-semibold" x-text="status"></div>
      </div>

      <!-- SOAL -->
      <template x-if="current">
        <div class="border-2 rounded-xl p-4 text-center bg-gray-50">

          <div class="text-2xl font-bold">
            <span x-text="current.book"></span>
            <span x-text="current.chapter"></span> :
            <span class="text-red-600">?</span>
          </div>

          <div class="mt-4 text-gray-700 text-sm">
            "<span x-text="current.verse_text"></span>"
          </div>

        </div>
      </template>

      <!-- INPUT -->
      <div class="flex justify-center mt-5">
        <input
          x-ref="input"
          type="number"
          x-model="answer"
          :disabled="isLocked"
          @keyup.enter="submit()"
          class="w-40 text-center text-lg border-2 rounded-lg p-2"
          placeholder="Jawaban"
        />
      </div>

      <!-- ACTION -->
      <div class="mt-4 flex justify-center">
        <button @click="submit()"
          :disabled="!answer || isLocked"
          class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-50">
          Submit
        </button>
      </div>

      <!-- FEEDBACK -->
      <div class="text-center mt-4 text-sm font-semibold">

        <template x-if="lastWinnerName">
          <span class="text-green-600">
            ✔ <span x-text="lastWinnerName"></span> menjawab benar!
          </span>
        </template>

        <template x-if="isTimeout">
          <span class="text-red-500">
            ⏱ Waktu habis!
          </span>
        </template>

      </div>

    </div>
  </div>
</div>

<script>
function multiplayerGame(){
  return {

    roomCode: "{{ $code }}",
    playerId: "{{ session('player_id') }}",

    players: [],
    current: null,

    answer: '',
    timeLeft: 0,

    status: '',

    isLocked: false,
    lastWinner: null,
    lastWinnerName: '',
    isTimeout: false,

    poller: null,

    init(){
      this.fetchState();

      this.poller = setInterval(() => {
        this.fetchState();
      }, 1000);
    },

    fetchState(){
      fetch(`/alkitab/multiplayer/state/${this.roomCode}`)
        .then(r => r.json())
        .then(data => {

          this.players = data.players;
          this.current = data.question;
          this.timeLeft = data.time_left;

          this.isTimeout = false;

          // STATUS
          if(data.answered_by){
            this.status = 'Sudah dijawab';
          } else if(this.timeLeft <= 0){
            this.status = 'Waktu habis';
          } else {
            this.status = 'Menunggu jawaban...';
          }

          // LOCK
          if(data.answered_by){

            this.isLocked = true;
            this.lastWinner = data.answered_by;

            const winner = this.players.find(p => p.id === data.answered_by);
            this.lastWinnerName = winner ? winner.name : '';

          } else {

            this.isLocked = false;
            this.lastWinner = null;
            this.lastWinnerName = '';

          }

          // TIMEOUT FLAG
          if(this.timeLeft <= 0){
            this.isTimeout = true;
          }

          // AUTO FOCUS
          if(!this.isLocked){
            this.$nextTick(() => {
              this.$refs.input?.focus();
            });
          }

        });
    },

    submit(){

      if(this.isLocked || !this.answer) return;

      fetch(`/alkitab/multiplayer/answer`, {
        method: 'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({
          room_code: this.roomCode,
          answer: this.answer,
          player_id: this.playerId
        })
      });

      this.answer = '';
    },

    startGame(){
      fetch(`/alkitab/multiplayer/start/${this.roomCode}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      });
    }

  }
}
</script>
@endsection