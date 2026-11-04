@extends('layouts.app')

@section('title', 'Dashboard & Visuals')
@section('page_name', 'Dashboard & Visual Analytics')

@section('content')
<div x-data="dashboardPage()" x-init="initDashboard()" class="space-y-6">

  <!-- HERO HEADER & GREETING -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Operations Intelligence</h2>
        <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-100 flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span> Live AI Stream
        </span>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Real-time triage, incoming workload trends, and deadline analytics.</p>
    </div>

    <!-- Quick action buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <a href="/tasks" class="inline-flex items-center gap-2 text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-2xl shadow-sm transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        <span>View All Tasks</span>
      </a>

      <button x-on:click="triggerBatchAnalysis()" :disabled="analyzing" class="inline-flex items-center gap-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2.5 rounded-2xl shadow-sm transition">
        <svg class="w-4 h-4 text-amber-300" :class="analyzing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <span x-text="analyzing ? 'Analyzing Batch...' : 'Run Gemini Analysis'"></span>
      </button>
    </div>
  </div>

  <!-- BATCH PROGRESS BAR IF ACTIVE -->
  <div x-show="progress" class="bg-indigo-50 border border-indigo-200/90 rounded-2xl p-4 shadow-sm" x-cloak>
    <div class="flex items-center justify-between text-xs font-bold text-indigo-950 mb-2">
      <span class="flex items-center gap-2">
        <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Batch Analyzing Messages with Gemini (spacing requests for rate limit)...
      </span>
      <span x-text="`${progress?.done || 0} / ${progress?.total || 0} messages processed`"></span>
    </div>
    <div class="w-full bg-indigo-200/80 rounded-full h-2.5 overflow-hidden">
      <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="`width: ${progress ? Math.round((progress.done / progress.total) * 100) : 0}%`"></div>
    </div>
  </div>

  <!-- TOP NUMERICAL KPI CARDS (Inspired by Reference Images 2 & 3) -->
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
    <!-- Card 1: Total Messages -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Inbox</span>
        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs">✉️</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="stats.total_messages ?? 0"></span>
        <span class="text-[11px] text-slate-500 block mt-0.5">Imported &amp; Manual</span>
      </div>
    </div>

    <!-- Card 2: AI Analyzed -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Analyzed</span>
        <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xs">✨</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="stats.analyzed_messages ?? 0"></span>
        <span class="text-[11px] text-purple-600 font-semibold block mt-0.5" x-text="stats.total_messages > 0 ? `${Math.round((stats.analyzed_messages / stats.total_messages) * 100)}% triaged` : '0% triaged'"></span>
      </div>
    </div>

    <!-- Card 3: Pending Tasks -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending</span>
        <div class="w-7 h-7 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">⏳</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="stats.pending_tasks ?? 0"></span>
        <span class="text-[11px] text-amber-600 font-semibold block mt-0.5">Awaiting Action</span>
      </div>
    </div>

    <!-- Card 4: In Progress -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">In Progress</span>
        <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs">⚡</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="stats.in_progress_tasks ?? 0"></span>
        <span class="text-[11px] text-blue-600 font-semibold block mt-0.5">Underway</span>
      </div>
    </div>

    <!-- Card 5: Completed -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Completed</span>
        <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">✅</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="stats.completed_tasks ?? 0"></span>
        <span class="text-[11px] text-emerald-600 font-semibold block mt-0.5" x-text="`${analytics.completion_rate || 0}% finished`"></span>
      </div>
    </div>

    <!-- Card 6: High Priority Alert (Highlighted as in reference) -->
    <div class="bg-gradient-to-br from-rose-50 to-white p-4 sm:p-5 rounded-2xl border-2 border-rose-300 shadow-sm flex flex-col justify-between hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Urgent Focus</span>
        <div class="w-7 h-7 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-xs font-black">!</div>
      </div>
      <div class="mt-2">
        <span class="text-3xl font-black text-rose-700 tracking-tight" x-text="stats.high_priority_open ?? 0"></span>
        <span class="text-[11px] text-rose-600 font-semibold block mt-0.5">High Priority Open</span>
      </div>
    </div>
  </div>

  <!-- VISUAL CHARTS SECTION (Chart.js Grid) -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Chart 1: Daily Inflow Timeline (2 Columns) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-slate-900">Message Inflow &amp; Timeline Activity</h3>
          <p class="text-xs text-slate-500">Incoming message traffic timeline resolved from received timestamps</p>
        </div>
        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-3 py-1 rounded-xl">7-Day Trajectory</span>
      </div>
      <div class="relative h-64 w-full">
        <canvas id="timelineChart"></canvas>
      </div>
    </div>

    <!-- Chart 2: Category Distribution Donut Chart (1 Column) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-slate-900">Category Breakdown</h3>
          <p class="text-xs text-slate-500">Classified by Gemini AI Intent</p>
        </div>
        <div class="text-xs font-extrabold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-xl" x-text="`${stats.analyzed_messages || 0} msgs`"></div>
      </div>
      <div class="relative h-56 w-full flex items-center justify-center">
        <canvas id="categoryDonutChart"></canvas>
      </div>
      <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 pt-2 border-t border-slate-100">
        <div>Client Requests: <strong class="text-slate-900" x-text="analytics.categories?.['Client Request'] || 0"></strong></div>
        <div>Complaints: <strong class="text-slate-900" x-text="analytics.categories?.['Complaint'] || 0"></strong></div>
        <div>Urgent Asks: <strong class="text-slate-900" x-text="analytics.categories?.['Urgent Request'] || 0"></strong></div>
        <div>Meetings: <strong class="text-slate-900" x-text="analytics.categories?.['Meeting'] || 0"></strong></div>
      </div>
    </div>
  </div>

  <!-- SECOND VISUAL ROW: Priority Distribution & High-Priority Attention List -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Chart 3: Priority vs Status Stacked Bar Chart -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-slate-900">Priority &amp; Status Matrix</h3>
          <p class="text-xs text-slate-500">Distribution of tasks across priority tiers</p>
        </div>
        <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-2.5 py-1 rounded-xl">Tasks</span>
      </div>
      <div class="relative h-60 w-full">
        <canvas id="priorityBarChart"></canvas>
      </div>
    </div>

    <!-- Radial Metric Widget & Response KPI (Inspired by Reference Images 2 & 3) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-slate-900">Operational Health</h3>
          <p class="text-xs text-slate-500">Efficiency and pending response metrics</p>
        </div>
        <span class="text-xs text-slate-400 font-medium">KPIs</span>
      </div>

      <!-- Two Circular / Bar Metric Indicators -->
      <div class="space-y-4 my-auto">
        <!-- Metric 1: Task Completion Rate -->
        <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-100 space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700">Task Completion Rate</span>
            <span class="font-black text-emerald-600 text-sm" x-text="`${analytics.completion_rate || 0}%`"></span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-700" :style="`width: ${analytics.completion_rate || 0}%`"></div>
          </div>
          <span class="text-[11px] text-slate-500 block" x-text="`${stats.completed_tasks || 0} of ${((stats.pending_tasks||0) + (stats.in_progress_tasks||0) + (stats.completed_tasks||0))} tasks completed`"></span>
        </div>

        <!-- Metric 2: Messages Requiring Response -->
        <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-100 space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700">Response Requirement</span>
            <span class="font-black text-amber-600 text-sm" x-text="`${analytics.reply_rate || 0}%`"></span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
            <div class="bg-amber-500 h-2 rounded-full transition-all duration-700" :style="`width: ${analytics.reply_rate || 0}%`"></div>
          </div>
          <span class="text-[11px] text-slate-500 block" x-text="`${stats.needs_reply || 0} communications require client replies`"></span>
        </div>
      </div>

      <!-- Quick AI daily summary pill -->
      <div class="bg-indigo-50/70 p-3.5 rounded-2xl border border-indigo-100 flex items-center justify-between">
        <div class="text-xs">
          <span class="font-bold text-indigo-950 block">AI Briefing Ready</span>
          <span class="text-indigo-900/70 text-[11px]">Generate synthesized operations digest</span>
        </div>
        <button x-on:click="$root.closest('body').querySelector('button[x-on\\:click*=\'openBriefingModal\']').click()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
          View
        </button>
      </div>
    </div>

    <!-- High-Priority Schedule / Urgent Task Watchlist (Inspired by Reference Images 2 & 3) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-slate-900">Urgent Attention Stream</h3>
          <p class="text-xs text-slate-500">Upcoming deadlines and critical tasks</p>
        </div>
        <span class="text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200" x-text="`${analytics.urgent_tasks?.length || 0} items`"></span>
      </div>

      <div class="space-y-2.5 overflow-y-auto max-h-72 pr-1">
        <template x-for="t in analytics.urgent_tasks || []" :key="t.id">
          <div class="p-3 rounded-2xl border transition" :class="t.priority==='High' ? 'bg-rose-50/40 border-rose-200' : 'bg-slate-50 border-slate-200/80'">
            <div class="flex items-start justify-between gap-2">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                :class="{
                  'bg-rose-100 text-rose-700 border border-rose-200': t.priority==='High',
                  'bg-amber-100 text-amber-700 border border-amber-200': t.priority==='Medium',
                  'bg-emerald-100 text-emerald-700 border border-emerald-200': t.priority==='Low'
                }" x-text="t.priority"></span>

              <span x-show="t.due_date" class="text-[11px] font-bold text-slate-600 flex items-center gap-1">
                📅 <span x-text="t.due_date"></span>
              </span>
              <span x-show="!t.due_date" class="text-[10px] text-slate-400 italic">No deadline</span>
            </div>

            <div class="font-bold text-xs text-slate-900 mt-1.5" x-text="t.title"></div>
            <div x-show="t.message" class="text-[11px] text-slate-500 truncate mt-0.5" x-text="`From: ${t.message.subject || t.message.external_id}`"></div>
          </div>
        </template>

        <div x-show="!analytics.urgent_tasks || analytics.urgent_tasks.length === 0" class="text-center py-8 text-xs text-slate-400 italic">
          No urgent items open. All clear!
        </div>
      </div>

      <a href="/tasks" class="block text-center text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline pt-2">
        Open Full Tasks Board &rarr;
      </a>
    </div>

  </div>

  <!-- RECENT INBOX TRIAGE STREAM (Mini Table inspired by Reference Images 1 & 3) -->
  <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="font-bold text-base text-slate-900">Recent Inbox Triage Stream</h3>
        <p class="text-xs text-slate-500">Latest communications processed by ActionFlow AI</p>
      </div>
      <a href="/tasks?view=messages" class="text-xs font-bold text-indigo-600 hover:underline">View All Messages &rarr;</a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
          <tr>
            <th class="py-3 px-4">Message</th>
            <th class="py-3 px-4">Sender</th>
            <th class="py-3 px-4">AI Category</th>
            <th class="py-3 px-4">Priority</th>
            <th class="py-3 px-4">Reply Expected?</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="m in analytics.recent_messages || []" :key="m.id">
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900 truncate max-w-xs" x-text="m.subject || '(No Subject)'"></div>
                <div class="text-[11px] text-slate-400 truncate max-w-sm mt-0.5" x-text="m.content"></div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-medium text-slate-800" x-text="m.sender_name"></div>
                <div class="text-[11px] text-slate-400" x-text="m.sender_email"></div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 rounded-full font-medium bg-slate-100 text-slate-700" x-text="m.analysis?.category || 'Pending'"></span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]"
                  :class="{
                    'bg-red-100 text-red-700': m.analysis?.priority==='High',
                    'bg-amber-100 text-amber-700': m.analysis?.priority==='Medium',
                    'bg-emerald-100 text-emerald-700': m.analysis?.priority==='Low'
                  }" x-text="m.analysis?.priority || 'Pending'"></span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span x-show="m.analysis?.requires_response" class="bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-full text-[10px] border border-amber-200">Yes</span>
                <span x-show="!m.analysis?.requires_response" class="text-slate-400">No</span>
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <a :href="`/tasks?open=${m.id}`" class="text-indigo-600 font-semibold hover:underline">Inspect</a>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardPage() {
  return {
    stats: {},
    analytics: {},
    progress: null,
    analyzing: false,
    charts: {},

    async initDashboard() {
      await this.loadData();
      this.initCharts();
    },

    async loadData() {
      try {
        const [statsRes, analyticsRes] = await Promise.all([
          fetch('/api/stats', { headers: { 'Accept': 'application/json' } }),
          fetch('/api/analytics', { headers: { 'Accept': 'application/json' } })
        ]);
        this.stats = await statsRes.json();
        this.analytics = await analyticsRes.json();
      } catch (e) {
        console.error('Failed to load dashboard data:', e);
      }
    },

    initCharts() {
      // 1. Timeline Chart (Area / Spline)
      const timelineCtx = document.getElementById('timelineChart')?.getContext('2d');
      if (timelineCtx && this.analytics.daily_inflow) {
        const labels = Object.keys(this.analytics.daily_inflow);
        const data = Object.values(this.analytics.daily_inflow);

        if (this.charts.timeline) this.charts.timeline.destroy();
        this.charts.timeline = new Chart(timelineCtx, {
          type: 'line',
          data: {
            labels: labels.length ? labels : ['Oct 28', 'Oct 29', 'Oct 30', 'Oct 31', 'Nov 1', 'Nov 2', 'Nov 3'],
            datasets: [{
              label: 'Incoming Messages',
              data: data.length ? data : [2, 4, 5, 3, 4, 6, 4],
              borderColor: '#4F46E5',
              backgroundColor: 'rgba(79, 70, 229, 0.08)',
              fill: true,
              tension: 0.4,
              borderWidth: 2.5,
              pointBackgroundColor: '#4F46E5',
              pointRadius: 4,
              pointHoverRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: { backgroundColor: '#0F172A', titleFont: { size: 12 }, bodyFont: { size: 12 }, cornerRadius: 8 }
            },
            scales: {
              x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748B' } },
              y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0, font: { size: 11 }, color: '#64748B' } }
            }
          }
        });
      }

      // 2. Category Donut Chart
      const categoryCtx = document.getElementById('categoryDonutChart')?.getContext('2d');
      if (categoryCtx) {
        const cats = this.analytics.categories || {};
        const catLabels = Object.keys(cats).length ? Object.keys(cats) : ['Client Request', 'Complaint', 'Meeting', 'Follow-up', 'Urgent Request', 'Informational'];
        const catData = Object.keys(cats).length ? Object.values(cats) : [5, 3, 4, 4, 3, 4];

        if (this.charts.category) this.charts.category.destroy();
        this.charts.category = new Chart(categoryCtx, {
          type: 'doughnut',
          data: {
            labels: catLabels,
            datasets: [{
              data: catData,
              backgroundColor: ['#4F46E5', '#EF4444', '#F59E0B', '#3B82F6', '#EC4899', '#10B981', '#64748B', '#8B5CF6'],
              borderWidth: 0,
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
              legend: { display: false },
              tooltip: { backgroundColor: '#0F172A', cornerRadius: 8 }
            }
          }
        });
      }

      // 3. Priority Bar Chart
      const priorityCtx = document.getElementById('priorityBarChart')?.getContext('2d');
      if (priorityCtx) {
        const p = this.analytics.task_priorities || {};
        if (this.charts.priority) this.charts.priority.destroy();
        this.charts.priority = new Chart(priorityCtx, {
          type: 'bar',
          data: {
            labels: ['High Priority', 'Medium Priority', 'Low Priority'],
            datasets: [{
              label: 'Tasks',
              data: [p['High'] || 0, p['Medium'] || 0, p['Low'] || 0],
              backgroundColor: ['#EF4444', '#F59E0B', '#10B981'],
              borderRadius: 8,
              barThickness: 32
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: { backgroundColor: '#0F172A', cornerRadius: 8 }
            },
            scales: {
              x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
              y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0 } }
            }
          }
        });
      }
    },

    async triggerBatchAnalysis() {
      this.analyzing = true;
      try {
        const res = await fetch('/api/messages?analysis_status=pending&per_page=200', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const pending = data.data || [];
        if (!pending.length) {
          alert('All messages are already analyzed!');
          this.analyzing = false;
          return;
        }

        for (let i = 0; i < pending.length; i++) {
          this.progress = { done: i, total: pending.length };
          try {
            await fetch(`/api/messages/${pending[i].id}/analyze`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
              }
            });
          } catch (e) {
            console.warn(e);
          }
          await this.loadData();
          this.initCharts();
          if (i < pending.length - 1) {
            await new Promise(r => setTimeout(r, 6500));
          }
        }
      } catch (err) {
        alert(err.message);
      } finally {
        this.progress = null;
        this.analyzing = false;
      }
    }
  };
}
</script>
@endpush
