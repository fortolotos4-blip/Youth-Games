@extends('layouts.app')

@section('content')

<div class="max-w-5xl mx-auto py-10 px-6">

<h1 class="text-3xl font-bold mb-8 text-center">
Sambung Kalimat Lagu
</h1>

<div class="grid md:grid-cols-2 gap-8">

<!-- SINGLE -->

<div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">

<h2 class="text-xl font-bold mb-3 text-center">
Single
</h2>

<p class="text-gray-600 mb-6">
Main sendiri tanpa batas soal.
</p>

<div class="text-center">

<a href="{{ route('song.single') }}"
class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">

Main

</a>

</div>

</div>


<!-- TEAM -->

<div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">

<h2 class="text-xl font-bold mb-3 text-center">
Team
</h2>

<p class="text-gray-600 mb-6">
Main bersama team dan bergantian menjawab sambungan lagu.
</p>

<div class="text-center">

<a href="{{ route('song.team') }}"
class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">

Main

</a>

</div>

</div>

</div>

</div>

@endsection