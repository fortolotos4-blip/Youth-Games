@extends('layouts.app')

@section('content')
<div
  x-data="multiplayerLobby('{{ $roomCode }}')"
  x-init="init()"
  class="max-w-xl mx-auto p-4"
>

  <!-- ROOM INFO -->
  <div class="bg-white rounded shadow p-4 mb-4 text-center">
    <h2 class="text-lg font-bold mb-1">Lobby Multiplayer Alkitab</h2>

    <p class="text-sm text-gray-600">
      Kode Room:
      <span class="font-mono font-bold text-indigo-600" x-text="room.code"></span>
    </p>

    <p class="mt-2 text-sm font-semibold" x-text="statusText"></p>
  </div>

  <!-- PLAYER LIST -->
  <div class="bg-white rounded shadow p-4 mb-4">
    <h3 class="font-semibold mb-3">Pemain</h3>

    <div class="grid grid-cols-2 gap-2">

      <!-- PLAYER -->
      <template x-for="player in players" :key="player.id">
        <div class="px-3 py-2 bg-gray-100 rounded text-sm font-medium flex justify-between">

          <span x-text="player.name"></span>

          <!-- HOST -->
          <span
            x-show="player.is_host"
            class="text-xs bg-indigo-500 text-white px-2 rounded"
          >
            Host
          </span>

        </div>
      </template>

      <!-- SLOT KOSONG -->
      <template x-for="n in emptySlots" :key="'empty-'+n">
        <div class="px-3 py-2 border border-dashed rounded text-sm text-gray-400 text-center">
          Menunggu…
        </div>
      </template>

    </div>
  </div>

  <!-- START BUTTON -->
  <div class="text-center mb-4" x-show="isHost && canStart && room.status === 'waiting'">
    <button
      @click="startGame"
      class="bg-green-600 text-white px-6 py-2 rounded font-semibold"
    >
      Mulai Game
    </button>
  </div>

  <!-- COUNTDOWN -->
  <div
    x-show="room.status === 'playing'"
    class="text-center text-lg font-semibold"
  >
    Game mulai dalam <span x-text="countdown"></span> detik...
  </div>

</div>

<script>
function multiplayerLobby(roomCode){
  return {
    room: {
      code: roomCode,
      status: 'waiting',
      max_players: 4,
      start_time: null
    },
    players: [],
    pollId: null,
    countdown: 0,
    myName: localStorage.getItem('player_name') || '',

    init(){
      this.fetchLobby();

      this.pollId = setInterval(() => {
        this.fetchLobby();
        this.updateCountdown();
      }, 2000);
    },

    fetchLobby(){
      fetch(`/alkitab/multiplayer/lobby-state/${this.room.code}`)
        .then(r => r.json())
        .then(data => {

          this.room = data.room;
          this.players = data.players;

          // 🔥 SYNC START
          if (this.room.status === 'playing' && this.room.start_time) {

            const now = Date.now();
            const start = new Date(this.room.start_time).getTime();

            if (now >= start) {
              clearInterval(this.pollId);

              window.location.href =
                `/alkitab/multiplayer/game/${this.room.code}`;
            }
          }
        });
    },

    updateCountdown(){
      if (this.room.start_time) {
        const now = Date.now();
        const start = new Date(this.room.start_time).getTime();

        this.countdown = Math.max(
          Math.ceil((start - now) / 1000),
          0
        );
      }
    },

    get emptySlots(){
      return Math.max(
        this.room.max_players - this.players.length,
        0
      );
    },

    get statusText(){
      if (this.room.status === 'waiting') {
        return `Menunggu pemain (${this.players.length}/${this.room.max_players})`;
      }
      return 'Game akan dimulai...';
    },

    get isHost(){
      return this.players.some(p =>
        p.name === this.myName && p.is_host
      );
    },

    get canStart(){
      return this.players.length >= 2;
    },

    startGame(){
      fetch(`/alkitab/multiplayer/start/${this.room.code}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          player_name: this.myName
        })
      });
    }
  }
}
</script>
@endsection