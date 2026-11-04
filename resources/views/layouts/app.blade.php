<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'ActionFlow AI') - Intelligent Inbox &amp; Task Management</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js CDN for Visual Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Alpine.js CDN -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
  
  <style>
    [x-cloak] { display: none !important; }
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
  </style>
</head>
<body class="bg-[#F8F9FC] text-slate-800 min-h-screen flex flex-col antialiased" x-data="globalApp()" x-init="initGlobal()" x-cloak>

  <!-- TOP HORIZONTAL NAVIGATION (No sidebar, all on top as requested) -->
  <header class="bg-white border-b border-slate-200/80 sticky top-0 z-40 px-4 sm:px-8 py-3.5 shadow-sm select-none">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
      
      <!-- Brand Logo on Left -->
      <a href="/" class="flex items-center gap-3 shrink-0 group">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-black shadow-md shadow-indigo-200 group-hover:scale-105 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div>
          <span class="text-base font-extrabold tracking-tight text-slate-900 block leading-tight">ActionFlow AI</span>
          <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block">Intelligent Inbox</span>
        </div>
      </a>

      <!-- PLATFORM VIEWS (Center Top Horizontal Nav with sleek pill design) -->
      <nav class="flex items-center gap-1.5 bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/70 overflow-x-auto max-w-full">
        <!-- 1. Dashboard & Visuals -->
        <a href="/" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->is('/') || request()->is('dashboard') ? 'bg-[#0F172A] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80' }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
          <span>Dashboard &amp; Visuals</span>
        </a>

        <!-- 2. Tasks & Messages -->
        <a href="/tasks" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->is('tasks*') ? 'bg-[#0F172A] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80' }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <span>Tasks &amp; Messages</span>
          <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ request()->is('tasks*') ? 'bg-slate-700 text-slate-200' : 'bg-slate-200 text-slate-700' }}" x-text="navStats.total_tasks || '0'"></span>
        </a>

        <!-- 3. Import & Automation -->
        <a href="/import" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->is('import*') ? 'bg-[#0F172A] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80' }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <span>Import &amp; Automation</span>
        </a>
      </nav>

      <!-- Action buttons & Status on Right -->
      <div class="flex items-center gap-2.5 shrink-0">
        <div class="hidden xl:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] font-semibold text-slate-600">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Gemini 2.5 Flash</span>
        </div>

        <button x-on:click="openBriefingModal()" class="inline-flex items-center gap-1.5 text-xs font-bold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-3.5 py-2 rounded-2xl transition shadow-sm">
          <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          <span>AI Briefing</span>
        </button>

        <a href="/tasks?action=new_task" class="inline-flex items-center gap-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-2xl transition shadow-sm">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          <span>+ Create Task</span>
        </a>
      </div>

    </div>
  </header>

  <!-- SUB-HEADER BREADCRUMB -->
  <div class="max-w-7xl w-full mx-auto px-4 sm:px-8 pt-4 flex items-center justify-between text-xs text-slate-400 select-none">
    <div class="flex items-center gap-2">
      <a href="/" class="hover:text-indigo-600 font-medium">ActionFlow AI</a>
      <span>/</span>
      <span class="font-bold text-slate-700">@yield('page_name', 'Dashboard')</span>
    </div>
    <div class="hidden sm:flex items-center gap-3 text-[11px]">
      <span>MySQL 8.0 • Connected</span>
      <span>•</span>
      <span>Asia/Kolkata</span>
    </div>
  </div>

  <!-- CONTENT BODY (Full width, centered) -->
  <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-8 space-y-6">
    @yield('content')
  </main>

  <!-- FOOTER -->
  <footer class="bg-white border-t border-slate-200/80 py-4 px-4 sm:px-8 text-center text-xs text-slate-400 select-none">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
      <span>ActionFlow AI • Intelligent Inbox &amp; Task Management</span>
      <span class="text-[11px]">Powered by Google Gemini 2.5 Flash &amp; Laravel 12</span>
    </div>
  </footer>

  <!-- DAILY BRIEFING MODAL -->
  <div x-show="briefingModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 space-y-4 border border-slate-100" x-on:click.outside="briefingModalOpen=false">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">☀️</div>
          <div>
            <h3 class="font-bold text-slate-900 text-base leading-tight">Executive Daily Briefing</h3>
            <span class="text-xs text-slate-500">Synthesized by Gemini 2.5 Flash from open tasks &amp; deadlines</span>
          </div>
        </div>
        <button x-on:click="briefingModalOpen=false" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">✕</button>
      </div>

      <div class="bg-gradient-to-br from-indigo-50/70 to-slate-50 border border-indigo-100/80 rounded-2xl p-5 text-xs text-slate-800 leading-relaxed whitespace-pre-wrap">
        <template x-if="briefingIsLoading">
          <div class="py-10 text-center text-indigo-600 font-semibold flex items-center justify-center gap-2">
            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Generating personalized operations briefing...
          </div>
        </template>
        <div x-show="!briefingIsLoading" x-text="briefingContent"></div>
      </div>

      <div class="flex justify-end pt-1">
        <button x-on:click="briefingModalOpen=false" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">Close Briefing</button>
      </div>
    </div>
  </div>

  <!-- TOAST NOTIFICATION -->
  <div x-show="globalToast" x-transition.opacity.duration.300ms class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white text-xs sm:text-sm font-medium px-4 py-3 rounded-2xl shadow-2xl border border-slate-700 flex items-center gap-2.5 max-w-md">
    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <span x-text="globalToast"></span>
  </div>

  <script>
    function globalApp() {
      return {
        navStats: { total_tasks: 0 },
        briefingModalOpen: false,
        briefingIsLoading: false,
        briefingContent: '',
        globalToast: '',

        async initGlobal() {
          try {
            const r = await fetch('/api/stats', { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            this.navStats.total_tasks = (d.pending_tasks || 0) + (d.in_progress_tasks || 0) + (d.completed_tasks || 0);
          } catch(e) {}
        },

        async openBriefingModal() {
          this.briefingModalOpen = true;
          this.briefingIsLoading = true;
          this.briefingContent = '';
          try {
            const r = await fetch('/api/briefing', { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            this.briefingContent = d.briefing || 'No briefing generated.';
          } catch(e) {
            this.briefingContent = `Could not generate briefing: ${e.message}`;
          } finally {
            this.briefingIsLoading = false;
          }
        },

        toast(msg) {
          this.globalToast = msg;
          setTimeout(() => { if (this.globalToast === msg) this.globalToast = ''; }, 3000);
        }
      };
    }
  </script>

  @stack('scripts')
</body>
</html>
