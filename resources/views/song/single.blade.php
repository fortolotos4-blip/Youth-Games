@extends('layouts.app')

@section('content')

<div x-data="game()" class="max-w-xl mx-auto">

    <!-- Kotak Shuffle -->
    <div class="bg-white border rounded-lg p-10 text-center mb-6">

        <h2 
        class="text-4xl font-bold mb-4 transition-all duration-150"
        x-text="display"></h2>

        <button 
            @click="startShuffle"
            :disabled="loading || finished"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">

            Mulai

        </button>

    </div>


    <!-- Slot Jawaban -->
    <div class="bg-white border rounded-lg p-6">

        <div class="grid grid-cols-3 gap-3">

            <template x-for="(item,index) in slots" :key="index">

                <div class="border p-4 rounded text-center bg-gray-50">

                    <div class="text-xs text-gray-400 mb-1">
                        <span x-text="'No ' + (index+1)"></span>
                    </div>

                    <div class="font-semibold text-sm min-h-[20px]" x-text="item"></div>

                </div>

            </template>

        </div>

    </div>

</div>



<!-- Popup Hasil -->
<div 
x-show="showPopup"
x-cloak
class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">

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



<!-- Popup Game Selesai -->
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



<script>

function game(){

return {

display:'?',
result:'',

loading:false,
showPopup:false,
finished:false,

shuffleInterval:null,

slots:[
    '',
    '',
    ''
],

usedIds:[],

shuffleWords:[
'🎵',
'?',
'...',
'🎶',
'🔥',
'💡',
'✨'
],



startShuffle(){

    if(this.loading || this.finished) return

    this.loading = true

    let index = 0

    // animasi shuffle teks
    this.shuffleInterval = setInterval(()=>{

        this.display = this.shuffleWords[
            Math.floor(Math.random()*this.shuffleWords.length)
        ]

    },100)



    setTimeout(()=>{

        clearInterval(this.shuffleInterval)

        this.getRandomLyric()

    },2500)

},



getRandomLyric(){

fetch("{{ route('surprise.random') }}",{
method:'POST',
headers:{
'Content-Type':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},
body:JSON.stringify({
used:this.usedIds
})
})
.then(res=>res.json())
.then(data=>{

this.result = data.lyric
this.currentId = data.id

this.display = '🎵'

this.showPopup = true
this.loading = false

})

},



confirmResult(){

this.showPopup=false

this.usedIds.push(this.currentId)

for(let i=0;i<this.slots.length;i++){

if(this.slots[i] === ''){

this.slots[i] = this.result
break

}

}

this.checkGame()

},



checkGame(){

let filled = this.slots.every(v => v !== '')

if(filled){

this.finished=true

fetch("{{ route('surprise.record') }}",{
method:'POST',
headers:{
'Content-Type':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},
body:JSON.stringify({
result:this.slots
})
})

}

},



resetGame(){

this.slots = ['','','']
this.usedIds = []
this.display='?'
this.finished=false

}

}

}

</script>

@endsection