@extends('layouts.app')

@section('content')
<div x-data="bibleguessSingle()" x-init="init()">

  <!-- GAME AREA -->
  <div :class="showSummary ? 'pointer-events-none blur-sm' : ''"
       class="max-w-3xl mx-auto p-4 transition">

    <!-- TOAST -->
    <div x-show="toast.show"
         x-transition
         class="fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white text-sm z-50"
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         x-text="toast.message">
    </div>

    <!-- RULES -->
    <div x-show="showRules" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white p-6 rounded w-96">
        <h3 class="text-xl font-bold">Aturan Game</h3>
        <p class="mt-2 text-sm">
          Tebak ayat, pasal, atau kitab dari potongan ayat.
        </p>
        <div class="text-right mt-4">
          <button @click="start()" class="px-3 py-2 bg-green-600 text-white rounded">
            Mulai
          </button>
        </div>
      </div>
    </div>

    <!-- MAIN -->
    <div class="bg-white p-4 sm:p-6 rounded shadow max-w-xl mx-auto">

      <!-- MODE SELECTOR -->
      <div class="flex justify-center gap-2 mb-4">
        <button @click="setMode('verse')"
          :class="mode==='verse' ? 'bg-indigo-600 text-white' : 'bg-gray-200'"
          class="px-3 py-1 rounded">
          Ayat
        </button>

        <button @click="setMode('chapter')"
          :class="mode==='chapter' ? 'bg-indigo-600 text-white' : 'bg-gray-200'"
          class="px-3 py-1 rounded">
          Pasal
        </button>

        <button @click="setMode('book')"
          :class="mode==='book' ? 'bg-indigo-600 text-white' : 'bg-gray-200'"
          class="px-3 py-1 rounded">
          Kitab
        </button>
      </div>

      <!-- TIMER -->
      <div class="flex justify-between mb-4 text-sm">
        <div>
          ⏱️ Soal:
          <b :class="timeLeft <= 5 ? 'text-red-600' : ''" x-text="timeLeft"></b>s
        </div>
        <div>
          ⏳ Sesi:
          <b x-text="sessionTimeLeft"></b>s
        </div>
      </div>

      <!-- SOAL DINAMIS -->
      <div class="text-center mb-4" x-show="current">

        <!-- MODE AYAT -->
        <template x-if="mode === 'verse'">
          <div class="text-2xl font-bold">
            <span x-text="current.book"></span>
            <span x-text="current.chapter"></span> :
            <span class="text-red-600">?</span>
          </div>
        </template>

        <!-- MODE PASAL -->
        <template x-if="mode === 'chapter'">
          <div class="text-2xl font-bold">
            <span x-text="current.book"></span> ? :
            <span x-text="current.verse"></span>
          </div>
        </template>

        <!-- MODE KITAB -->
        <template x-if="mode === 'book'">
          <div class="text-2xl font-bold">
            ? <span x-text="current.chapter"></span> :
            <span x-text="current.verse"></span>
          </div>
        </template>

        <div class="mt-4 text-gray-700 text-sm">
          "<span x-text="current.verse_text"></span>"
        </div>
      </div>

      <!-- INPUT -->
      <div class="flex justify-center mt-4">
        <input
          :type="mode === 'book' ? 'text' : 'number'"
          x-model="answer"
          @keyup.enter="submit()"
          :disabled="isSubmitting"
          class="w-40 text-center text-xl border rounded p-2"
          placeholder="Jawaban..."
        />
      </div>

      <!-- ACTION -->
      <div class="mt-4 flex justify-center gap-3">
        <button @click="submit()" :disabled="!answer"
          class="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50">
          Submit
        </button>

        <button @click="skip()"
          class="px-4 py-2 bg-gray-400 text-white rounded">
          Skip
        </button>
      </div>

    </div>
  </div>

  <!-- SUMMARY -->
  <div x-show="showSummary"
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
    <div class="bg-white w-[90%] max-w-sm rounded-xl p-6 text-center">

      <h2 class="text-xl font-bold mb-4">Game Selesai</h2>

      <div class="text-sm space-y-1 mb-4">
        <p>Total: <b x-text="summary.total"></b></p>
        <p>Benar: <b x-text="summary.correct"></b></p>
        <p>Salah: <b x-text="summary.wrong"></b></p>
      </div>

      <a href="{{ route('dashboard') }}"
         class="block py-3 bg-indigo-600 text-white rounded">
        Kembali
      </a>
    </div>
  </div>

</div>

<script>
function bibleguessSingle(){
  return {

    mode: 'verse',

    questions: @json($questions ?? []),
    availableQuestions: [],
    current: null,

    answer: '',
    isSubmitting: false,

    timeLeft: 20,
    timerId: null,

    sessionTimeLeft: 300,
    sessionTimerId: null,

    showRules: true,
    showSummary: false,

    attempts: [],
    summary: { correct: 0, wrong: 0, total: 0 },

    toast: { show:false, message:'', type:'success' },

    init(){
      this.availableQuestions = [...this.questions];
    },

    setMode(newMode){
      this.mode = newMode;

      this.availableQuestions = [...this.questions];
      this.attempts = [];

      this.pickRandomQuestion();
      this.startTimer();
    },

    start(){
      this.showRules = false;
      this.startSessionTimer();
      this.pickRandomQuestion();
      this.startTimer();
    },

    pickRandomQuestion(){
      if(this.availableQuestions.length === 0){
        this.finishSession();
        return;
      }

      const i = Math.floor(Math.random() * this.availableQuestions.length);
      this.current = this.availableQuestions[i];

      this.answer = '';
      this.timeLeft = this.current.time_limit_seconds ?? 20;
    },

    startTimer(){
      clearInterval(this.timerId);
      this.timerId = setInterval(()=>{
        this.timeLeft--;
        if(this.timeLeft <= 0){
          clearInterval(this.timerId);
          this.onTimeout();
        }
      },1000);
    },

    startSessionTimer(){
      clearInterval(this.sessionTimerId);
      this.sessionTimerId = setInterval(()=>{
        this.sessionTimeLeft--;
        if(this.sessionTimeLeft <= 0){
          this.finishSession();
        }
      },1000);
    },

    submit(){
      if(!this.current || this.isSubmitting) return;

      let answer = this.answer.toString().trim();

      if(this.mode !== 'book'){
        if(!answer || answer < 1 || answer > 200){
          this.showToast("Masukkan angka valid", "error");
          return;
        }
      }

      this.isSubmitting = true;
      clearInterval(this.timerId);

      fetch("/alkitab/single/answer", {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({
          question_id: this.current.id,
          answer: answer,
          mode: this.mode
        })
      })
      .then(r=>r.json())
      .then(data=>{
        const correct = !!data.correct;

        this.attempts.push({correct});

        if(correct){
          this.availableQuestions = this.availableQuestions.filter(
            q => q.id !== this.current.id
          );
          this.showToast("Benar!", "success");
        } else {
          this.showToast("Salah!", "error");
        }

        setTimeout(()=>{
          this.isSubmitting = false;
          this.pickRandomQuestion();
          this.startTimer();
        },600);
      });
    },

    skip(){
      clearInterval(this.timerId);
      this.pickRandomQuestion();
      this.startTimer();
    },

    onTimeout(){
      this.attempts.push({correct:false});
      this.pickRandomQuestion();
      this.startTimer();
    },

    finishSession(){
      clearInterval(this.timerId);
      clearInterval(this.sessionTimerId);

      const total = this.attempts.length;
      const correct = this.attempts.filter(a=>a.correct).length;

      this.summary = {
        total,
        correct,
        wrong: total - correct
      };

      this.showSummary = true;
    },

    showToast(msg,type){
      this.toast.message = msg;
      this.toast.type = type;
      this.toast.show = true;

      setTimeout(()=> this.toast.show=false,1200);
    }

  }
}
</script>
@endsection