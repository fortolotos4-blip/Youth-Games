@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 flex justify-center pt-12">

  <div class="w-full max-w-md bg-white p-6 rounded-2xl shadow-lg">

    <h1 class="text-2xl font-bold text-center mb-6">
      Multiplayer Alkitab
    </h1>

    <!-- INPUT NAMA -->
    <div class="mb-4">
      <input type="text"
        id="name"
        placeholder="Masukkan nama..."
        class="w-full border-2 rounded-lg p-2 text-center">
    </div>

    <!-- CREATE ROOM -->
    <form method="POST" action="{{ route('alkitab.multiplayer.create') }}" class="mb-4">
      @csrf
      <input type="hidden" name="name" id="createName">

      <button type="submit"
        onclick="syncName('create')"
        class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold">
        Buat Room
      </button>
    </form>

    <!-- JOIN ROOM -->
    <form method="GET" action="" onsubmit="return joinRoom()">

      <input type="text"
        id="code"
        placeholder="Kode Room (contoh: ABC12)"
        class="w-full border-2 rounded-lg p-2 text-center mb-3">

      <button type="submit"
        class="w-full py-3 bg-green-600 text-white rounded-lg font-semibold">
        Gabung Room
      </button>

    </form>

  </div>

</div>

<script>
function syncName(type){
  const name = document.getElementById('name').value;
  document.getElementById('createName').value = name;
}

function joinRoom(){
  const name = document.getElementById('name').value;
  const code = document.getElementById('code').value;

  if(!name){
    alert('Isi nama dulu');
    return false;
  }

  if(!code){
    alert('Masukkan kode room');
    return false;
  }

  // simpan nama ke localStorage (optional)
  localStorage.setItem('player_name', name);

  window.location.href = `/alkitab/multiplayer/play/${code}`;
  return false;
}
</script>
@endsection