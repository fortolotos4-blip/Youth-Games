@extends('layouts.app')

@section('content')
<div
  x-data="multiplayerGame('{{ $code }}')"
  x-init="init()"
  class="min-h-screen bg-gray-100 flex justify-center pt-10"
>

  <div class="w-full max-w-4xl p-4">

    <!-- ROOM -->
    <div class="text-center mb-4">
      <h2 class="font-bold">
        Room: <span class="text-indigo-600" x-text="roomCode"></span>
      </h2>
    </div>

    <!-- PLAYERS -->
    <div class="flex gap-2 overflow-x-auto mb-4">
      <template x-for="p in players" :key="p.id">
        <div
          class="min-w-[90px] bg-white rounded-lg p-2 text-center shadow"
          :class="p.id === lastWinner ? 'ring-2 ring-green-500' : ''"
        >
          <div class="text-sm font-semibold" x-text="p.name"></div>
          <div class="text-lg font-bold text-indigo-600" x-text="p.score"></div>
        </div>
      </template>
    </div>

    <!-- MAIN -->
    <div class="bg-white p-6 rounded-2xl shadow-lg">

      <!-- TIMER -->
      <div class="flex justify-between text-sm mb-3">
        <div>
          ⏱ <b :class="timeLeft <= 5 ? 'text-red-600' : ''" x-text="timeLeft"></b>s
        </div>
        <div class="font-semibold" x-text="status"></div>
      </div>

      <!-- SOAL -->
      <template x-if="question">
        <div class="border-2 rounded-xl p-4 text-center bg-gray-50">

          <!-- MODE AYAT -->
          <div class="text-2xl font-bold">
            <span x-text="question.book"></span>
            <span x-text="question.chapter"></span> :
            <span class="text-red-600">?</span>
          </div>

          <div class="mt-4 text-gray-700 text-sm">
            "<span x-text="question.verse_text"></span>"
          </div>

        </div>
      </template>

      <!-- INPUT -->
      <div class="flex justify-center mt-5">
        <input
          x-ref="input"
          type="number"
          x-model="answer"
          :disabled="isLocked || roomStatus !== 'playing'"
          @keyup.enter="submit"
          class="w-40 text-center text-lg border-2 rounded-lg p-2"
          placeholder="Jawaban"
        />
      </div>

      <!-- BUTTON -->
      <div class="mt-4 flex justify-center">
        <button
          @click="submit"
          :disabled="!answer || isLocked"
          class="px-4 py-2 bg-indigo-600 text-white rounded"
        >
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
function multiplayerGame(code){
  return {

    roomCode: code,
    playerId: "{{ session('player_id') }}",

    players: [],
    question: null,

    answer: '',
    timeLeft: 0,

    roomStatus: '',
    status: '',

    isLocked: false,
    lastWinner: null,
    lastWinnerName: '',
    isTimeout: false,

    poller: null,
    isFetching: false,

    init(){
      this.fetchState();

      this.poller = setInterval(() => {
        if(!this.isFetching){
          this.fetchState();
        }
      }, 2000);
    },

    async fetchState(){

      this.isFetching = true;

      try {

        const res = await fetch(`/alkitab/multiplayer/state/${this.roomCode}`, {
          credentials: 'same-origin'
        });

        const text = await res.text();

        // ❗ HANDLE RESPONSE KOSONG
        if(!text){
          console.warn('Empty response');
          return;
        }

        let data;

        try {
          data = JSON.parse(text);
        } catch(e){
          console.error('Invalid JSON:', text);
          return;
        }

        // ❗ HANDLE ERROR DARI BACKEND
        if(data.error){
          console.warn(data.error);
          return;
        }

        // ✅ UPDATE STATE
        this.players = data.players || [];
        this.question = data.question;
        this.timeLeft = data.time_left ?? 0;
        this.roomStatus = data.room_status ?? 'waiting';

        this.isTimeout = false;

        // ✅ STATUS
        if(!this.question){
          this.status = 'Menunggu soal...';
          return;
        }

        if(data.answered_by){
          this.status = 'Sudah dijawab';
        } else if(this.timeLeft <= 0){
          this.status = 'Waktu habis';
        } else {
          this.status = 'Cepat jawab!';
        }

        // ✅ LOCK
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

        if(this.timeLeft <= 0){
          this.isTimeout = true;
        }

        // AUTO FOCUS
        if(!this.isLocked){
          this.$nextTick(() => {
            this.$refs.input?.focus();
          });
        }

      } catch (err) {
        console.warn('Fetch error:', err);
      } finally {
        this.isFetching = false;
      }
    },

    submit(){

      if(this.isLocked || !this.answer) return;

      fetch(`/alkitab/multiplayer/answer`, {
        method: 'POST',
        credentials: 'same-origin',
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
    }

  }
}
</script>
@endsection