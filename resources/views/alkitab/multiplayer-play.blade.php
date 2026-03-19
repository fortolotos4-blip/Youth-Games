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
      <template x-for="p in players" :key="'player-'+p.id">
        <div
          class="min-w-[90px] bg-white rounded-lg p-2 text-center shadow"
          :class="p.id === lastWinner ? 'ring-2 ring-green-500' : ''"
        >
          <div class="text-sm font-semibold" x-text="p.name"></div>
          <div class="text-lg font-bold text-indigo-600" x-text="p.score"></div>
        </div>
      </template>
    </div>

    <!-- MAIN CARD -->
    <div class="bg-white p-6 rounded-2xl shadow-lg">

      <!-- TIMER -->
      <div class="flex justify-between text-sm mb-2">
        <div>
          ⏱ Soal:
          <b :class="timeLeft <= 5 ? 'text-red-600' : ''"
             x-text="timeLeft"></b>s
        </div>

        <div>
          ⏳ Game:
          <b :class="sessionLeft <= 10 ? 'text-red-600' : ''"
             x-text="sessionLeft"></b>s
        </div>
      </div>

      <!-- STATUS -->
      <div class="text-center text-sm font-semibold mb-3"
           x-text="status"></div>

      <!-- EMPTY STATE -->
      <template x-if="!question && roomStatus === 'playing'">
        <div class="text-center text-gray-500 py-6">
          Menunggu soal berikutnya...
        </div>
      </template>

      <!-- SOAL -->
      <template x-if="question">
        <div class="border-2 rounded-xl p-4 text-center bg-gray-50">

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
          :disabled="isLocked || roomStatus !== 'playing' || !question"
          @keyup.enter="submit"
          class="w-40 text-center text-lg border-2 rounded-lg p-2"
          placeholder="Jawaban"
        />
      </div>

      <!-- BUTTON -->
      <div class="mt-4 flex justify-center">
        <button
          @click="submit"
          :disabled="!answer || isLocked || isSubmitting"
          class="px-4 py-2 bg-indigo-600 text-white rounded"
        >
          <span x-show="!isSubmitting">Submit</span>
          <span x-show="isSubmitting">Loading...</span>
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

    </div> <!-- ✅ END CARD -->

  </div>

  <!-- MODAL FINISH -->
  <template x-if="roomStatus === 'finished'">
    <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl p-6 w-80 text-center">

        <div class="text-3xl mb-2">🏁</div>
        <h2 class="font-bold text-lg mb-3">Game Selesai</h2>

        <!-- LEADERBOARD -->
        <div class="text-sm space-y-2 mb-4">
          <template x-for="(p,index) in players" :key="'rank-'+p.id">
            <div class="flex justify-between"
                 :class="index === 0 ? 'font-bold text-green-600' : ''">
              <span x-text="p.name"></span>
              <span x-text="p.score"></span>
            </div>
          </template>
        </div>

        <a href="/alkitab/menu"
           class="block mt-3 bg-indigo-600 text-white rounded py-2">
          Kembali ke Menu
        </a>

      </div>
    </div>
  </template>

</div>

<script>
function multiplayerGame(code){
  return {

    roomCode: code,

    players: [],
    question: null,

    answer: '',
    timeLeft: 0,
    sessionLeft: 0,

    roomStatus: '',
    status: '',

    isLocked: false,
    lastWinner: null,
    lastWinnerName: '',
    isTimeout: false,

    poller: null,
    isFetching: false,
    isSubmitting: false,

    init(){
      this.fetchState();

      this.poller = setInterval(() => {
        if(!this.isFetching){
          this.fetchState();
        }
      }, 2000);
      // ✅ CLEANUP SAAT PAGE DITINGGALKAN
  window.addEventListener('beforeunload', () => {
    clearInterval(this.poller);
  });
    },

    async fetchState(){

  if(this.isFetching) return;
  this.isFetching = true;

  try {

    const res = await fetch(`/alkitab/multiplayer/state/${this.roomCode}`, {
      credentials: 'same-origin'
    });

    if(!res.ok){
      console.warn('Bad response');
      return;
    }

    let data;
    try {
      data = await res.json();
    } catch(e){
      console.error('Invalid JSON');
      return;
    }

    if(data.error){
      console.warn(data.error);
      return;
    }

    // ✅ PLAYERS (SORT LEADERBOARD)
    this.players = (data.players || []).sort((a,b)=>b.score - a.score);

    // ✅ BASIC STATE
    this.question = data.question;
    this.timeLeft = data.time_left ?? 0;
    this.sessionLeft = data.session_left ?? 0;
    this.roomStatus = data.room_status ?? 'waiting';

    this.isTimeout = false;

    // 🏁 GAME FINISHED (PRIORITY HIGHEST)
    if(this.roomStatus === 'finished'){
      this.isLocked = true;
      clearInterval(this.poller);
      return; // ⛔ stop semua logic di bawah
    }

    // ❗ NO QUESTION
    if(!this.question){
      this.status = 'Menunggu soal...';
      this.isLocked = true;
      return;
    }

    // ✅ STATUS
    if(data.answered_by){
      this.status = 'Sudah dijawab';
    } else if(this.timeLeft <= 0){
      this.status = 'Waktu habis';
    } else {
      this.status = 'Cepat jawab!';
    }

    // ✅ LOCK LOGIC (FIXED)
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

    // ⏱ TIMEOUT FLAG
    if(this.timeLeft <= 0){
      this.isTimeout = true;
    }

    // 🎯 AUTO FOCUS
    if(!this.isLocked && this.roomStatus === 'playing'){
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

  // ❌ BLOCK kondisi tidak valid
  if(
    this.isLocked ||
    !this.answer ||
    this.roomStatus !== 'playing' ||
    this.isSubmitting
  ) return;

  this.isSubmitting = true;

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
    })
  })
  .then(res => res.json())
  .then(data => {

    // ❗ handle error backend
    if(data.error){
      console.warn(data.error);
      return;
    }

    // (optional) feedback cepat
    if(data.correct){
      this.status = 'Benar!';
    } else {
      this.status = 'Salah!';
    }

  })
  .catch(err => {
    console.error('Submit error:', err);
  })
  .finally(() => {
    this.isSubmitting = false;
  });

  // clear input langsung (UX cepat)
  this.answer = '';
}

  }
}
</script>
@endsection