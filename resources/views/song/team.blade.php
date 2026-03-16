@extends('layouts.app')

@section('content')

<div x-data="teamGame()" class="max-w-4xl mx-auto">

<!-- SHUFFLE BOX -->

<div class="bg-white border rounded-lg p-10 text-center mb-6">

<h2
class="text-5xl font-bold mb-4"
:class="loading ? 'music-float' : ''"
x-text="display">
</h2>

<div class="text-lg font-bold mb-4">

Giliran :
<span
:class="teamTurn==1 ? 'text-green-600' : 'text-blue-600'"
x-text="'Team ' + teamTurn">
</span>

</div>

<button
@click="startShuffle"
:disabled="loading || finished"
class="bg-indigo-500 hover:bg-indigo-600 text-white px-8 py-3 rounded text-lg">

Mulai

</button>

</div>


<!-- TEAM SLOT -->

<div class="grid grid-cols-2 gap-6">

<!-- TEAM 1 -->

<div class="bg-white border rounded-lg p-6">

<h2 class="font-bold text-center mb-4 text-green-600">
Team 1
</h2>

<div class="flex flex-col gap-3">

<template x-for="(item,index) in team1Slots" :key="index">

<div
class="border p-4 rounded text-center"
:class="item ? 'bg-green-100 animate-slot' : 'bg-gray-50'">

<div class="text-xs text-gray-400">
No <span x-text="index+1"></span>
</div>

<div class="font-semibold" x-text="item || '...'"></div>

</div>

</template>

</div>

</div>


<!-- TEAM 2 -->

<div class="bg-white border rounded-lg p-6">

<h2 class="font-bold text-center mb-4 text-blue-600">
Team 2
</h2>

<div class="flex flex-col gap-3">

<template x-for="(item,index) in team2Slots" :key="index">

<div
class="border p-4 rounded text-center"
:class="item ? 'bg-blue-100 animate-slot' : 'bg-gray-50'">

<div class="text-xs text-gray-400">
No <span x-text="index+1"></span>
</div>

<div class="font-semibold" x-text="item || '...'"></div>

</div>

</template>

</div>

</div>

</div>



<!-- POPUP RESULT -->

<div
x-show="showPopup"
x-cloak
class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">

<div class="bg-white p-6 rounded-lg shadow text-center w-80">

<h3 class="text-lg font-bold mb-4">
🎵 Sambung Lagu
</h3>

<p class="text-xl font-semibold mb-5" x-text="result"></p>

<button
@click="confirmResult"
class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">

OK

</button>

</div>

</div>



<!-- GAME FINISH -->

<div
x-show="finished"
x-cloak
class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">

<div class="bg-white p-6 rounded-lg shadow text-center w-80">

<h3 class="text-xl font-bold text-green-600 mb-3">

🎉 Game Selesai

</h3>

<p class="mb-4">

Semua slot sudah terisi

</p>

<button
@click="resetGame"
class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">

Main Lagi

</button>

</div>

</div>



<style>

[x-cloak]{
display:none!important
}

@keyframes slotIn{
0%{opacity:0;transform:translateY(20px)}
100%{opacity:1;transform:translateY(0)}
}

.animate-slot{
animation:slotIn .4s ease
}

@keyframes musicFloat{
0%{transform:translate(0,0) rotate(0deg)}
25%{transform:translate(-10px,-6px) rotate(-10deg)}
50%{transform:translate(8px,-10px) rotate(8deg)}
75%{transform:translate(10px,6px) rotate(-6deg)}
100%{transform:translate(0,0) rotate(0deg)}
}

.music-float{
animation:musicFloat 1.2s ease-in-out infinite
}

</style>



<script>

function teamGame(){

return{

display:'🎵',

result:'',

loading:false,
showPopup:false,
finished:false,

teamTurn:1,

team1Slots:['','',''],
team2Slots:['','',''],

usedIds:[],

currentId:null,


startShuffle(){

if(this.loading || this.finished) return

this.loading=true

this.display='🎵'

setTimeout(()=>{

this.getRandomLyric()

},2200)

},


getRandomLyric(){

fetch("{{ route('song.random') }}",{

credentials:'same-origin',

method:'POST',

headers:{
'Content-Type':'application/json',
'Accept':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},

body:JSON.stringify({
used:this.usedIds
})

})

.then(res=>res.json())

.then(data=>{

this.result=data.lyric
this.currentId=data.id

this.showPopup=true
this.loading=false

})

.catch(()=>{

this.loading=false

})

},


confirmResult(){

this.showPopup=false

if(!this.usedIds.includes(this.currentId)){
this.usedIds.push(this.currentId)
}


if(this.teamTurn===1){

for(let i=0;i<this.team1Slots.length;i++){

if(this.team1Slots[i]===''){
this.team1Slots[i]=this.result
break
}

}

}else{

for(let i=0;i<this.team2Slots.length;i++){

if(this.team2Slots[i]===''){
this.team2Slots[i]=this.result
break
}

}

}


this.switchTurn()

this.checkGame()

},



switchTurn(){

if(this.teamTurn===1){

if(this.team2Slots.includes('')){
this.teamTurn=2
}

}else{

if(this.team1Slots.includes('')){
this.teamTurn=1
}

}

},



checkGame(){

let team1Full=this.team1Slots.every(v=>v!=='')
let team2Full=this.team2Slots.every(v=>v!=='')

if(team1Full && team2Full){
this.finished=true
}

},



resetGame(){

this.team1Slots=['','','']
this.team2Slots=['','','']

this.teamTurn=1

this.usedIds=[]

this.result=''
this.currentId=null

this.loading=false
this.showPopup=false
this.finished=false

this.display='🎵'

}

}

}

</script>

@endsection