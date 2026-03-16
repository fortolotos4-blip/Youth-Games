@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-4">
  <h2 class="text-2xl mb-4 font-bold"> Sambung Kalimat Lagu</h2>

  <div class="grid grid-cols-2 gap-4">
    <div class="bg-white p-4 rounded shadow">
      <h3 class="font-semibold mb-2">Single</h3>
      <p class="text-sm text-gray-600">Main tanpa batas soal.</p>
      <div class="mt-4 text-right">
        <a href="{{ route('song.single') }}" class="px-4 py-2 bg-blue-600 text-white rounded">Main</a>
      </div>
    </div>

    <div class="bg-white p-4 rounded shadow">
      <h3 class="font-semibold mb-2">Tim</h3>
      <p class="text-sm text-gray-600">Main bersama tim, bergantian menjawab.</p>
      <div class="mt-4 text-right">
        <a href="{{ route('song.team') }}" class="px-4 py-2 bg-red-600 text-white rounded">Main</a>
      </div>
    </div>

  </div>
</div>
@endsection
