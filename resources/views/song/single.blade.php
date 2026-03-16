@extends('layouts.app')

@section('content')

<div x-data="game()" class="max-w-xl mx-auto">

<!-- SHUFFLE BOX -->

<div class="bg-white border rounded-lg p-10 text-center mb-6">

<h2
class="text-4xl font-bold mb-4 transition-all duration-300"
x-text="display">
</h2>

<button
@click="startShuffle"
:disabled="loading || finished"
class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded text-lg">

Mulai

</button>

</div>


<!-- SLOT -->

<div class="bg-white border rounded-lg p-6">

<div class="flex flex-col gap-4">

<template x-for="(item,index) in slots" :key="index">

<div
class="border rounded bg-gray-50 transition-all duration-500 flex items-center justify-center py-6 relative overflow-hidden"
:class="item ? 'bg-green-100 shadow-md animate-slot' : ''">

<!-- nomor slot -->

<div class="absolute left-3 text-sm text-gray-400 font-bold">

<span x-text="index+1"></span>

</div>

<!-- text lyric -->

<div
class="text-lg font-semibold text-center"
x-text="item || '...'">
</div>

</div>

</template>

</div>

</div>

</div>



<!-- POPUP RESULT -->

<div
x-show="showPopup"
x-cloak
class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">

<div class="bg-white p-6 rounded-lg shadow text-center w-80">

<h3 class="text-lg font-bold mb-4">
🎵 Sambung Lagu
</h3>

<p
class="text-xl font-semibold mb-5"
x-text="revealedText">
</p>

<button
@click="confirmResult"
class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">

OK

</button>

</div>

</div>



<!-- POPUP FINISH -->

<div
x-show="finished"
x-cloak
class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">

<div class="bg-white p-6 rounded-lg shadow text-center w-80">

<h3 class="text-xl font-bold text-green-600 mb-3">

🎉 Game Selesai

</h3>

<p class="text-gray-600 mb-4">

Semua bagian lagu sudah muncul

</p>

<button
@click="resetGame"
class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">

Main Lagi

</button>

</div>

</div>

<style>

@keyframes slotIn {

0%{
opacity:0;
transform:translateY(20px) scale(0.9);
}

50%{
opacity:1;
transform:translateY(-3px) scale(1.05);
}

100%{
opacity:1;
transform:translateY(0) scale(1);
}

}

.animate-slot{
animation:slotIn .45s ease;
}

</style>

<script>

function game(){

return {

display:'✨',

result:'',
revealedText:'',

loading:false,
showPopup:false,
finished:false,

shuffleInterval:null,

slots:['','',''],

usedIds:[],

shuffleWords:[
'✨','🎵','🔥','🎶','💡','?','...'
],



startShuffle(){

if(this.loading || this.finished) return

this.loading=true

this.shuffleInterval=setInterval(()=>{

this.display=this.shuffleWords[
Math.floor(Math.random()*this.shuffleWords.length)
]

},80)



setTimeout(()=>{

clearInterval(this.shuffleInterval)

this.getRandomLyric()

},2200)

},



getRandomLyric(){

fetch("{{ route('song.random') }}",{

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

if(data.reset){
this.usedIds=[]
}

this.result=data.lyric
this.currentId=data.id

this.display='🎵'

this.showPopup=true

this.loading=false

this.revealText()

})

},



revealText(){

this.revealedText=''

let i=0

let text=this.result

let interval=setInterval(()=>{

this.revealedText+=text[i]

i++

if(i>=text.length){

clearInterval(interval)

}

},40)

},



confirmResult(){

this.showPopup=false

this.usedIds.push(this.currentId)

for(let i=0;i<this.slots.length;i++){

if(this.slots[i]===''){

this.slots[i]=this.result

break

}

}

this.checkGame()

},



checkGame(){

let filled=this.slots.every(v=>v!=='')

if(filled){

this.finished=true

fetch("{{ route('song.record') }}",{

method:'POST',

headers:{
'Content-Type':'application/json',
'Accept':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},

body:JSON.stringify({
result:this.slots
})

})

}

},



resetGame(){

this.slots=['','','']
this.usedIds=[]
this.display='✨'
this.finished=false

}

}

}

</script>

@endsection