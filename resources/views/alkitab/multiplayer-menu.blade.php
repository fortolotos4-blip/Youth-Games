@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-6">

  <h2 class="text-2xl font-bold mb-6 text-center">
    Multiplayer Cari Ayat Alkitab
  </h2>

  <!-- CREATE ROOM -->
  <div class="bg-white rounded shadow p-4 mb-6">
    <h3 class="font-semibold mb-3">Buat Room</h3>

    <form
      x-data="createRoom()"
      @submit.prevent="submit"
      class="space-y-3"
    >

      <!-- NAMA -->
      <input
        x-model="player_name"
        required
        maxlength="30"
        class="w-full border rounded px-3 py-2 text-center"
        placeholder="Nama pemain"
      >

      <!-- JUMLAH PLAYER -->
      <select
        x-model="max_players"
        class="w-full border rounded px-3 py-2 text-center"
      >
        <option value="2">2 Pemain</option>
        <option value="3">3 Pemain</option>
        <option value="4">4 Pemain</option>
      </select>

      <!-- BUTTON -->
      <button
        class="w-full bg-indigo-600 text-white rounded py-2 font-semibold"
      >
        Buat Room
      </button>

    </form>
  </div>

  <!-- JOIN ROOM -->
  <div class="bg-white rounded shadow p-4">
    <h3 class="font-semibold mb-3">Gabung Room</h3>

    <form
      x-data="joinRoom()"
      @submit.prevent="submit"
      class="space-y-3"
    >

      <!-- NAMA -->
      <input
        x-model="player_name"
        required
        maxlength="30"
        class="w-full border rounded px-3 py-2 text-center"
        placeholder="Nama pemain"
      >

      <!-- KODE -->
      <input
        x-model="room_code"
        required
        class="w-full border rounded px-3 py-2 uppercase text-center"
        placeholder="Kode Room"
      >

      <!-- BUTTON -->
      <button
        class="w-full bg-green-600 text-white rounded py-2 font-semibold"
      >
        Gabung Room
      </button>

    </form>
  </div>

</div>

<script>
function createRoom(){
  return {
    player_name: '',
    max_players: 2,

    submit(){
      if(!this.player_name){
        alert('Isi nama dulu');
        return;
      }

      // simpan nama
      localStorage.setItem('player_name', this.player_name);

      fetch('/alkitab/multiplayer/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          name: this.player_name,
          max_players: this.max_players
        })
      })
      .then(r => r.json())
      .then(d => {
        if(d.room_code){
          window.location.href =
            `/alkitab/multiplayer/lobby/${d.room_code}`;
        }
      });
    }
  }
}

function joinRoom(){
  return {
    player_name: '',
    room_code: '',

    submit(){
      if(!this.player_name){
        alert('Isi nama dulu');
        return;
      }

      if(!this.room_code){
        alert('Masukkan kode room');
        return;
      }

      const code = this.room_code.toUpperCase();

      // simpan nama
      localStorage.setItem('player_name', this.player_name);

      fetch('/alkitab/multiplayer/join', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          name: this.player_name,
          code: code
        })
      })
      .then(r => r.json())
      .then(d => {
        if(d.success){
          window.location.href =
            `/alkitab/multiplayer/lobby/${code}`;
        } else {
          alert(d.error || 'Gagal join room');
        }
      });
    }
  }
}
</script>
@endsection