@extends('layouts.app')

@section('content')
<div class="mb-6">
  <h2 class="text-3xl font-extrabold text-gray-800">
    Dashboard
  </h2>
  <p class="text-gray-500 mt-1">
    Pilih permainan yang ingin kamu mainkan
  </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

  <!-- QUIZ -->
  <a href="{{ route('quiz.index') }}"
     class="group bg-white rounded-xl p-6 shadow hover:shadow-xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">❓</div>
    <h3 class="font-bold text-lg mb-1">Quiz Rohani</h3>
    <p class="text-sm text-gray-500">Uji pengetahuan imanmu dengan menjawab soal</p>
    <span class="mt-4 inline-block text-indigo-600 text-sm font-semibold group-hover:underline">
      Mulai →
    </span>
  </a>

  <!-- TEBAK GAMBAR -->
  <a href="{{ route('guess.menu') }}"
     class="group bg-white rounded-xl p-6 shadow hover:shadow-xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">🖼️</div>
    <h3 class="font-bold text-lg mb-1">Kisah Alkitab</h3>
    <p class="text-sm text-gray-500"> Tebak tokoh dan peristiwa dalam teka teki gambar</p>
    <span class="mt-4 inline-block text-indigo-600 text-sm font-semibold group-hover:underline">
      Mainkan →
    </span>
  </a>

  <!-- TTS -->
  <a href="{{ route('tts.menu') }}"
     class="group bg-gradient-to-br from-indigo-500 to-blue-600 text-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">🧩</div>
    <h3 class="font-bold text-lg mb-1">
      Teka-Teki Silang Rohani
    </h3>
    <p class="text-sm text-indigo-100">
      Rangkai kata menjadi sebuah kalimat yang utuh
    </p>
    <span class="mt-4 inline-block text-white text-sm font-semibold group-hover:underline">
      Main →
    </span>
  </a>

  <!-- PUZZLE GAMBAR -->
  <a href="{{ route('puzzle.index') }}"
     class="group bg-white rounded-xl p-6 shadow hover:shadow-xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">🧩</div>
    <h3 class="font-bold text-lg mb-1">Puzzle Misterius</h3>
    <p class="text-sm text-gray-500">Susun kepingan gambar menjadi gambar yang indah</p>
    <span class="mt-4 inline-block text-indigo-600 text-sm font-semibold group-hover:underline">
      Mainkan →
    </span>
  </a>

  <!-- SURPRISE -->
  <a href="{{ route('surprise.index') }}"
     class="group bg-white rounded-xl p-6 shadow hover:shadow-xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">🎁</div>
    <h3 class="font-bold text-lg mb-1">Hadiah Sinterklas</h3>
    <p class="text-sm text-gray-500">Kejutan spesial dari santa untuk kamu</p>
    <span class="mt-4 inline-block text-indigo-600 text-sm font-semibold group-hover:underline">
      Buka →
    </span>
  </a>

  <!-- SONGS -->
  <a href="{{ route('song.menu') }}"
     class="group bg-white rounded-xl p-6 shadow hover:shadow-xl transition transform hover:-translate-y-1">
    <div class="text-4xl mb-3">🎵</div>
    <h3 class="font-bold text-lg mb-1">Sambung Lagu</h3>
    <p class="text-sm text-gray-500">Sambung lagu dengan kalimat yang ada</p>
    <span class="mt-4 inline-block text-indigo-600 text-sm font-semibold group-hover:underline">
      Buka →
    </span>
  </a>
</div>

@endsection
