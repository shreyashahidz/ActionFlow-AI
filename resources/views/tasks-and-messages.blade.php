@extends('layouts.app')

@section('title', 'Tasks Management & Messages')
@section('page_name', 'Tasks Management & Messages Analysis')

@section('content')
<div x-data="tasksAndMessagesPage()" x-init="initPage()" class="space-y-6">

  <!-- PAGE HEADER & ACTIONS (Inspired by Reference Image 1) -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Workload &amp; Inbox Triage</h2>
        <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-100" x-text="`${activeTab === 'tasks' ? tasks.length : messages.length} active items`"></span>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage AI-extracted and manual tasks, review incoming communications, and track deadlines.</p>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center gap-2.5">
      <a href="/api/tasks/export" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-2xl shadow-sm transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <span>Export CSV</span>
      </a>

      <button x-on:click="openNewTaskModal()" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-2xl shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span>+ Create Task</span>
      </button>
    </div>
  </div>

  <!-- VIEW SWITCHER & FILTER TABS (Inspired by Reference Image 1) -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-2">
    <!-- Main view tabs: Tasks vs Messages -->
    <div class="flex items-center gap-2">
      <button x-on:click="activeTab='tasks'" class="px-4 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2"
        :class="activeTab==='tasks' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'">
        <span>Tasks Board</span>
        <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab==='tasks' ? 'bg-slate-700 text-slate-200' : 'bg-slate-100 text-slate-600'" x-text="tasks.length"></span>
      </button>

      <button x-on:click="activeTab='messages'; if(!messages.length) loadMessages()" class="px-4 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2"
        :class="activeTab==='messages' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'">
        <span>Messages &amp; Analysis</span>
        <span class="px-2 py-0.5 rounded-full text-[10px]" :class="activeTab==='messages' ? 'bg-slate-700 text-slate-200' : 'bg-slate-100 text-slate-600'" x-text="messages.length"></span>
      </button>
    </div>

    <!-- Quick filter pill chips for Tasks (Reference Image 1) -->
    <div x-show="activeTab==='tasks'" class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
      <button x-on:click="filterStatus=''; filterPriority=''; filterOverdue=false; loadTasks()"
        class="px-3 py-1.5 rounded-xl font-semibold transition"
        :class="!filterStatus && !filterPriority && !filterOverdue ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-500 hover:bg-slate-100'">
        All (<span x-text="tasks.length"></span>)
      </button>
      <button x-on:click="filterPriority='High'; filterStatus=''; filterOverdue=false; loadTasks()"
        class="px-3 py-1.5 rounded-xl font-semibold transition"
        :class="filterPriority==='High' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'text-slate-500 hover:bg-slate-100'">
        High Priority (<span x-text="tasks.filter(x => x.priority==='High').length"></span>)
      </button>
      <button x-on:click="filterStatus='Pending'; filterPriority=''; filterOverdue=false; loadTasks()"
        class="px-3 py-1.5 rounded-xl font-semibold transition"
        :class="filterStatus==='Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'text-slate-500 hover:bg-slate-100'">
        Pending (<span x-text="tasks.filter(x => x.status==='Pending').length"></span>)
      </button>
      <button x-on:click="filterStatus='Completed'; filterPriority=''; filterOverdue=false; loadTasks()"
        class="px-3 py-1.5 rounded-xl font-semibold transition"
        :class="filterStatus==='Completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'text-slate-500 hover:bg-slate-100'">
        Completed (<span x-text="tasks.filter(x => x.status==='Completed').length"></span>)
      </button>
    </div>
  </div>

  <!-- ==================== SUB-SECTION 1: TASKS ==================== -->
  <div x-show="activeTab==='tasks'" class="space-y-4">

    <!-- SEARCH & FILTER TOOLBAR (Styled after Reference Image 1) -->
    <div class="bg-white p-3.5 rounded-3xl shadow-sm border border-slate-200/80 flex flex-wrap gap-2.5 items-center justify-between">
      <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
        <!-- Search anything... input -->
        <div class="relative flex-1 min-w-[220px]">
          <input x-model.debounce.300ms="searchQ" x-on:input="loadTasks()" type="text" placeholder="Search tasks, descriptions, or linked senders..."
            class="w-full pl-9 pr-4 py-2 text-xs font-medium rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Status Filter Dropdown -->
        <select x-model="filterStatus" x-on:change="loadTasks()" class="text-xs font-medium rounded-2xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:outline-none">
          <option value="">Status: All</option>
          <option value="Pending">Pending</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>

        <!-- Priority Filter Dropdown -->
        <select x-model="filterPriority" x-on:change="loadTasks()" class="text-xs font-medium rounded-2xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:outline-none">
          <option value="">Priority: All</option>
          <option value="High">High</option>
          <option value="Medium">Medium</option>
          <option value="Low">Low</option>
        </select>

        <!-- Overdue Checkbox -->
        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 px-3 py-2 bg-slate-50 rounded-2xl border border-slate-200 cursor-pointer select-none">
          <input type="checkbox" x-model="filterOverdue" x-on:change="loadTasks()" class="rounded text-indigo-600 focus:ring-indigo-500">
          <span>Overdue</span>
        </label>
      </div>

      <!-- View Switcher: Table vs Kanban -->
      <div class="flex items-center rounded-2xl border border-slate-200 p-1 bg-slate-50">
        <button x-on:click="taskDisplayMode='table'" :class="taskDisplayMode==='table' ? 'bg-white shadow-sm text-slate-900 font-bold' : 'text-slate-500 font-medium'" class="px-3 py-1.5 text-xs rounded-xl transition">
          Table
        </button>
        <button x-on:click="taskDisplayMode='kanban'" :class="taskDisplayMode==='kanban' ? 'bg-white shadow-sm text-slate-900 font-bold' : 'text-slate-500 font-medium'" class="px-3 py-1.5 text-xs rounded-xl transition">
          Kanban
        </button>
      </div>
    </div>

    <!-- TABLE VIEW (Modeled directly after Reference Image 1) -->
    <div x-show="taskDisplayMode==='table'" class="overflow-x-auto bg-white rounded-3xl shadow-sm border border-slate-200/80">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80">
          <tr>
            <th class="py-3.5 px-4">Priority</th>
            <th class="py-3.5 px-4">Task Details</th>
            <th class="py-3.5 px-4">Due Date</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4">Origin Communication</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="t in tasks" :key="t.id">
            <tr class="hover:bg-slate-50/70 transition group" :class="t.priority==='High' && t.status!=='Completed' ? 'bg-rose-50/30' : ''">
              <!-- Priority Badge -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                  :class="{
                    'bg-rose-100 text-rose-700 border border-rose-200': t.priority==='High',
                    'bg-amber-100 text-amber-700 border border-amber-200': t.priority==='Medium',
                    'bg-emerald-100 text-emerald-700 border border-emerald-200': t.priority==='Low'
                  }" x-text="t.priority"></span>
              </td>

              <!-- Title & Description -->
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900 text-xs sm:text-sm" :class="t.status==='Completed' ? 'line-through text-slate-400' : ''" x-text="t.title"></div>
                <div x-show="t.description" class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-relaxed" x-text="t.description"></div>
                
                <!-- Duplicate Badge -->
                <div x-show="t.duplicate_of" class="mt-1">
                  <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                    ⚠️ Possible duplicate of #<span x-text="t.duplicate_of"></span>
                  </span>
                </div>
              </td>

              <!-- Due Date -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span x-show="t.due_date" class="text-xs font-medium" :class="isOverdue(t) ? 'text-rose-600 font-black flex items-center gap-1' : 'text-slate-600'">
                  <span x-show="isOverdue(t)">⚠️</span>
                  <span x-text="t.due_date"></span>
                  <span x-show="isOverdue(t)" class="text-[10px] uppercase font-bold text-rose-500">(Overdue)</span>
                </span>
                <span x-show="!t.due_date" class="text-xs text-slate-400 italic">No deadline</span>
              </td>

              <!-- Status Dropdown (Inline Pill) -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <select :value="t.status" x-on:change="quickUpdate(t, {status: $event.target.value})"
                  class="text-xs font-bold rounded-xl border py-1 px-2.5 focus:outline-none"
                  :class="{
                    'bg-amber-50 text-amber-800 border-amber-200': t.status==='Pending',
                    'bg-blue-50 text-blue-800 border-blue-200': t.status==='In Progress',
                    'bg-emerald-50 text-emerald-800 border-emerald-200': t.status==='Completed'
                  }">
                  <option value="Pending">Pending</option>
                  <option value="In Progress">In Progress</option>
                  <option value="Completed">Completed</option>
                </select>
              </td>

              <!-- Origin Message Link -->
              <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                <template x-if="t.message">
                  <button x-on:click="inspectMessage(t.message_id)" class="text-indigo-600 hover:underline max-w-[200px] truncate block text-left font-medium">
                    <span class="font-mono text-[10px] bg-slate-100 text-slate-600 px-1 py-0.5 rounded mr-1" x-text="t.message.external_id"></span>
                    <span x-text="t.message.subject || 'Communication'"></span>
                  </button>
                </template>
                <template x-if="!t.message">
                  <span class="text-slate-400 italic">Manual Entry</span>
                </template>
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 whitespace-nowrap text-right text-xs">
                <button x-on:click="openEditModal(t)" class="text-slate-600 hover:text-indigo-600 font-semibold px-2 py-1 rounded-lg hover:bg-slate-100 transition mr-1">Edit</button>
                <button x-on:click="deleteTask(t)" class="text-rose-600 hover:text-rose-800 font-semibold px-2 py-1 rounded-lg hover:bg-rose-50 transition">Delete</button>
              </td>
            </tr>
          </template>

          <tr x-show="!tasks.length">
            <td colspan="6" class="py-12 text-center text-slate-400">
              <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <p class="font-bold text-slate-600 text-sm">No tasks match your current filter.</p>
              <p class="text-xs text-slate-400 mt-1">Try clearing filters or click "+ Create Task" to add one.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- KANBAN VIEW (Visual Workflow columns) -->
    <div x-show="taskDisplayMode==='kanban'" class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <template x-for="col in [
        {name:'Pending', title:'Pending Action', bg:'bg-amber-50/50', border:'border-amber-200', text:'text-amber-900'},
        {name:'In Progress', title:'In Execution', bg:'bg-blue-50/50', border:'border-blue-200', text:'text-blue-900'},
        {name:'Completed', title:'Completed', bg:'bg-emerald-50/50', border:'border-emerald-200', text:'text-emerald-900'}
      ]" :key="col.name">
        <div class="rounded-3xl border p-4 flex flex-col min-h-[460px]" :class="[col.bg, col.border]">
          <div class="flex items-center justify-between font-bold text-xs mb-3 px-1" :class="col.text">
            <span class="uppercase tracking-wider" x-text="col.title"></span>
            <span class="bg-white px-2 py-0.5 rounded-full border text-[11px] font-extrabold" x-text="tasks.filter(x => x.status === col.name).length"></span>
          </div>

          <div class="space-y-3 flex-1 overflow-y-auto max-h-[640px] pr-1">
            <template x-for="t in tasks.filter(x => x.status === col.name)" :key="t.id">
              <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80 space-y-2 hover:shadow-md transition">
                <div class="flex items-center justify-between gap-2">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                    :class="{
                      'bg-rose-100 text-rose-700': t.priority==='High',
                      'bg-amber-100 text-amber-700': t.priority==='Medium',
                      'bg-emerald-100 text-emerald-700': t.priority==='Low'
                    }" x-text="t.priority"></span>
                  <span x-show="t.due_date" class="text-[11px] font-semibold" :class="isOverdue(t) ? 'text-rose-600 font-bold' : 'text-slate-500'" x-text="t.due_date"></span>
                </div>

                <div class="font-bold text-xs text-slate-900 leading-tight" x-text="t.title"></div>
                <div x-show="t.description" class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed" x-text="t.description"></div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                  <template x-if="t.message">
                    <button x-on:click="inspectMessage(t.message_id)" class="text-indigo-600 hover:underline text-[11px] truncate max-w-[120px] font-medium" x-text="t.message.subject || 'Message'"></button>
                  </template>
                  <template x-if="!t.message">
                    <span class="text-slate-400 text-[10px] italic">Manual</span>
                  </template>

                  <div class="flex items-center gap-1">
                    <button x-show="col.name !== 'Pending'" x-on:click="quickUpdate(t, {status: 'Pending'})" title="Move to Pending" class="px-1.5 py-0.5 text-[10px] bg-slate-100 hover:bg-slate-200 rounded">«</button>
                    <button x-show="col.name !== 'In Progress'" x-on:click="quickUpdate(t, {status: 'In Progress'})" title="Move to In Progress" class="px-1.5 py-0.5 text-[10px] bg-blue-100 hover:bg-blue-200 text-blue-700 rounded font-semibold">Prog</button>
                    <button x-show="col.name !== 'Completed'" x-on:click="quickUpdate(t, {status: 'Completed'})" title="Mark Done" class="px-1.5 py-0.5 text-[10px] bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded font-bold">✓</button>
                    <button x-on:click="openEditModal(t)" class="text-slate-400 hover:text-slate-700 ml-1">✎</button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>

  </div>

  <!-- ==================== SUB-SECTION 2: MESSAGES & ANALYSIS ==================== -->
  <div x-show="activeTab==='messages'" class="space-y-4">
    <!-- Search & Filter Toolbar for Messages -->
    <div class="bg-white p-3.5 rounded-3xl shadow-sm border border-slate-200/80 flex flex-wrap gap-2.5 items-center justify-between">
      <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
        <div class="relative flex-1 min-w-[220px]">
          <input x-model.debounce.300ms="msgSearchQ" x-on:input="loadMessages()" type="text" placeholder="Search sender, subject, content, ID..."
            class="w-full pl-9 pr-4 py-2 text-xs font-medium rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <select x-model="msgFilterType" x-on:change="loadMessages()" class="text-xs font-medium rounded-2xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:outline-none">
          <option value="">Type: All</option>
          <option value="email">Email</option>
          <option value="message">Chat / WhatsApp</option>
        </select>

        <select x-model="msgFilterStatus" x-on:change="loadMessages()" class="text-xs font-medium rounded-2xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:outline-none">
          <option value="">Analysis: All</option>
          <option value="pending">Pending</option>
          <option value="analyzed">Analyzed</option>
          <option value="failed">Failed</option>
        </select>

        <select x-model="msgFilterCategory" x-on:change="loadMessages()" class="text-xs font-medium rounded-2xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:outline-none">
          <option value="">Category: All</option>
          <option value="Client Request">Client Request</option>
          <option value="Complaint">Complaint</option>
          <option value="Meeting">Meeting</option>
          <option value="Follow-up">Follow-up</option>
          <option value="Urgent Request">Urgent Request</option>
          <option value="Informational">Informational</option>
          <option value="No Action Needed">No Action Needed</option>
        </select>
      </div>

      <a href="/import" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 border border-indigo-100 px-3.5 py-2 rounded-2xl transition">
        <span>+ Import More Messages</span>
      </a>
    </div>

    <!-- Messages Table -->
    <div class="overflow-x-auto bg-white rounded-3xl shadow-sm border border-slate-200/80">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200/80">
          <tr>
            <th class="py-3.5 px-4">Message &amp; Snippet</th>
            <th class="py-3.5 px-4">Sender Details</th>
            <th class="py-3.5 px-4">Category &amp; Priority</th>
            <th class="py-3.5 px-4">Reply Needed?</th>
            <th class="py-3.5 px-4">Extracted Tasks</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="m in messages" :key="m.id">
            <tr class="hover:bg-slate-50/70 transition">
              <!-- Subject & Type -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold" x-text="m.external_id"></span>
                  <span class="text-[10px] uppercase px-1.5 py-0.5 rounded font-bold"
                    :class="m.type==='email' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                    x-text="m.type"></span>
                  <span x-show="m.attachments && m.attachments.length" class="text-slate-400 text-xs" title="Has attachments">📎</span>
                </div>
                <div class="font-bold text-slate-900 text-xs mt-1 cursor-pointer hover:text-indigo-600 transition" x-on:click="inspectMessage(m.id)">
                  <span x-text="m.subject || '(No Subject)'"></span>
                </div>
                <div class="text-[11px] text-slate-400 line-clamp-1 mt-0.5" x-text="m.content"></div>
              </td>

              <!-- Sender & Date -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-800" x-text="m.sender_name"></div>
                <div class="text-[11px] text-slate-400" x-text="m.sender_email"></div>
                <div class="text-[10px] text-slate-400 mt-0.5" x-text="new Date(m.sent_at).toLocaleString()"></div>
              </td>

              <!-- Category & Priority -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <template x-if="m.analysis">
                  <div class="space-y-1">
                    <span class="inline-block px-2 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-800 text-[10px]" x-text="m.analysis.category"></span>
                    <div>
                      <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                        :class="{
                          'bg-rose-100 text-rose-700': m.analysis.priority==='High',
                          'bg-amber-100 text-amber-700': m.analysis.priority==='Medium',
                          'bg-emerald-100 text-emerald-700': m.analysis.priority==='Low'
                        }" x-text="m.analysis.priority"></span>
                    </div>
                  </div>
                </template>
                <template x-if="!m.analysis">
                  <span class="text-xs text-slate-400 italic">Pending AI Analysis</span>
                </template>
              </td>

              <!-- Reply needed -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <template x-if="m.analysis">
                  <div>
                    <span x-show="m.analysis.requires_response" class="bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full text-[10px] border border-amber-200">Yes</span>
                    <span x-show="!m.analysis.requires_response" class="text-slate-400">No</span>
                  </div>
                </template>
                <template x-if="!m.analysis">
                  <span class="text-slate-300">-</span>
                </template>
              </td>

              <!-- Task count -->
              <td class="py-3.5 px-4 whitespace-nowrap font-bold text-slate-700" x-text="m.tasks_count ?? 0"></td>

              <!-- Actions -->
              <td class="py-3.5 px-4 whitespace-nowrap text-right space-x-1">
                <button x-on:click="inspectMessage(m.id)" class="text-indigo-600 hover:text-indigo-800 font-bold px-2 py-1 rounded-lg hover:bg-indigo-50 transition">
                  Inspect
                </button>
                <button x-on:click="analyzeSingleMessage(m, true)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-2.5 py-1 rounded-xl transition">
                  <span x-text="m.analysis_status==='analyzed' ? 'Re-analyze' : 'Analyze'"></span>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ==================== MODALS & DRAWERS ==================== -->

  <!-- 1. SLIDE-OVER MESSAGE DETAIL DRAWER -->
  <div x-show="currentMessage" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex justify-end" x-cloak>
    <div class="w-full max-w-2xl bg-white min-h-screen shadow-2xl p-6 sm:p-8 space-y-5 overflow-y-auto" x-on:click.outside="currentMessage=null">
      <!-- Drawer Header -->
      <div class="flex items-start justify-between border-b border-slate-100 pb-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold" x-text="currentMessage?.external_id"></span>
            <span class="text-xs uppercase px-2 py-0.5 rounded font-bold"
              :class="currentMessage?.type==='email' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
              x-text="currentMessage?.type"></span>
          </div>
          <h2 class="text-xl font-black text-slate-900 mt-2" x-text="currentMessage?.subject || '(No Subject)'"></h2>
          <div class="text-xs text-slate-500 mt-1">
            From: <strong class="text-slate-800" x-text="currentMessage?.sender_name"></strong> &lt;<span x-text="currentMessage?.sender_email"></span>&gt;
            • To: <span x-text="currentMessage?.recipient"></span>
          </div>
        </div>
        <button x-on:click="currentMessage=null" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">✕</button>
      </div>

      <!-- Original Message Box -->
      <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2">
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Original Communication Content</div>
        <div class="text-xs sm:text-sm text-slate-800 whitespace-pre-wrap leading-relaxed" x-text="currentMessage?.content"></div>

        <template x-if="currentMessage?.attachments && currentMessage.attachments.length">
          <div class="pt-2 border-t border-slate-200 mt-2">
            <span class="text-xs text-slate-500 font-semibold">Attachments:</span>
            <div class="flex flex-wrap gap-1.5 mt-1">
              <template x-for="att in currentMessage.attachments" :key="att">
                <span class="inline-flex items-center gap-1 text-xs bg-white px-2 py-1 rounded-xl border border-slate-200 font-medium text-slate-700">
                  📎 <span x-text="att"></span>
                </span>
              </template>
            </div>
          </div>
        </template>
      </div>

      <!-- AI Analysis Card -->
      <template x-if="currentMessage?.analysis">
        <div class="bg-gradient-to-br from-indigo-50/70 to-slate-50 rounded-2xl p-5 border border-indigo-100 space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">AI</div>
              <h3 class="font-black text-slate-900 text-sm">Gemini AI Analysis Result</h3>
            </div>
            <button x-on:click="analyzeSingleMessage(currentMessage, true)" class="text-xs bg-white hover:bg-slate-50 text-indigo-700 px-3 py-1 rounded-xl border border-indigo-200 font-bold transition">
              Re-analyze
            </button>
          </div>

          <!-- Metadata Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <div class="bg-white p-3 rounded-xl border border-indigo-100">
              <div class="text-slate-400 font-medium">Category</div>
              <div class="font-extrabold text-slate-900 mt-0.5" x-text="currentMessage.analysis.category"></div>
            </div>
            <div class="bg-white p-3 rounded-xl border border-indigo-100">
              <div class="text-slate-400 font-medium">Priority</div>
              <div class="mt-0.5 font-bold">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                  :class="{
                    'bg-rose-100 text-rose-700': currentMessage.analysis.priority==='High',
                    'bg-amber-100 text-amber-700': currentMessage.analysis.priority==='Medium',
                    'bg-emerald-100 text-emerald-700': currentMessage.analysis.priority==='Low'
                  }" x-text="currentMessage.analysis.priority"></span>
              </div>
            </div>
            <div class="bg-white p-3 rounded-xl border border-indigo-100">
              <div class="text-slate-400 font-medium">Reply Needed?</div>
              <div class="font-extrabold mt-0.5" :class="currentMessage.analysis.requires_response ? 'text-amber-600' : 'text-slate-600'" x-text="currentMessage.analysis.requires_response ? 'Yes' : 'No'"></div>
            </div>
            <div class="bg-white p-3 rounded-xl border border-indigo-100">
              <div class="text-slate-400 font-medium">Tasks Extracted</div>
              <div class="font-extrabold text-slate-900 mt-0.5" x-text="currentMessage.tasks ? currentMessage.tasks.length : 0"></div>
            </div>
          </div>

          <!-- Summary & Intent -->
          <div class="space-y-2 text-xs bg-white p-4 rounded-xl border border-indigo-100">
            <div>
              <span class="font-bold text-slate-700">Intent:</span>
              <span class="text-slate-900 ml-1 font-medium" x-text="currentMessage.analysis.intent"></span>
            </div>
            <div>
              <span class="font-bold text-slate-700">Summary:</span>
              <span class="text-slate-800 ml-1 leading-relaxed" x-text="currentMessage.analysis.summary"></span>
            </div>
          </div>

          <!-- Suggested Reply with Copy button -->
          <div class="bg-white p-4 rounded-xl border border-indigo-100 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                💬 Context-Aware Suggested Reply
              </span>
              <button x-on:click="copySuggestedReply(currentMessage.analysis.suggested_reply)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-xl bg-indigo-50 border border-indigo-100 transition">
                Copy Reply
              </button>
            </div>
            <div class="text-xs text-slate-700 italic bg-slate-50 p-3 rounded-xl border border-slate-200 leading-relaxed" x-text="currentMessage.analysis.suggested_reply || 'No reply suggested (informational / no action).'"></div>
          </div>
        </div>
      </template>

      <!-- Linked Tasks generated -->
      <div class="space-y-3">
        <h4 class="font-bold text-slate-900 text-sm">Actionable Tasks Extracted</h4>
        <div class="space-y-2">
          <template x-for="t in currentMessage?.tasks || []" :key="t.id">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                    :class="{
                      'bg-rose-100 text-rose-700': t.priority==='High',
                      'bg-amber-100 text-amber-700': t.priority==='Medium',
                      'bg-emerald-100 text-emerald-700': t.priority==='Low'
                    }" x-text="t.priority"></span>
                  <span class="font-bold text-xs text-slate-900" x-text="t.title"></span>
                </div>
                <div x-show="t.description" class="text-xs text-slate-500" x-text="t.description"></div>
                <div class="flex items-center gap-3 text-[11px] text-slate-500 pt-1">
                  <span>Due: <strong x-text="t.due_date || 'None'"></strong></span>
                  <span>Status: <strong x-text="t.status"></strong></span>
                </div>
              </div>
              <button x-on:click="openEditModal(t)" class="text-xs font-bold text-indigo-600 hover:underline">Edit</button>
            </div>
          </template>

          <div x-show="!currentMessage?.tasks || currentMessage?.tasks.length === 0" class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-2xl border text-center">
            No actionable tasks extracted (message is informational or pending analysis).
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. EDIT TASK MODAL -->
  <div x-show="editingTask" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-100" x-on:click.outside="editingTask=null">
      <div class="flex items-center justify-between border-b pb-3">
        <h3 class="font-bold text-slate-900 text-base">Edit Task Details</h3>
        <button x-on:click="editingTask=null" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
      </div>

      <form x-on:submit.prevent="saveTaskEdit()" class="space-y-3.5 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Title</label>
          <input type="text" x-model="editingTask.title" required class="w-full border rounded-2xl p-2.5 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Description</label>
          <textarea x-model="editingTask.description" rows="3" class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Priority</label>
            <select x-model="editingTask.priority" class="w-full border rounded-2xl p-2.5 bg-slate-50 font-semibold focus:outline-none">
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status</label>
            <select x-model="editingTask.status" class="w-full border rounded-2xl p-2.5 bg-slate-50 font-semibold focus:outline-none">
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Due Date</label>
          <input type="date" x-model="editingTask.due_date" class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none">
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t">
          <button type="button" x-on:click="editingTask=null" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-sm transition">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. ADD TASK MODAL -->
  <div x-show="newTaskModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-100" x-on:click.outside="newTaskModalOpen=false">
      <div class="flex items-center justify-between border-b pb-3">
        <h3 class="font-bold text-slate-900 text-base">Create New Task</h3>
        <button x-on:click="newTaskModalOpen=false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
      </div>

      <form x-on:submit.prevent="submitCreateTask()" class="space-y-3.5 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Task Title</label>
          <input type="text" x-model="newTaskData.title" required placeholder="e.g. Verify payment gateway callback endpoints" class="w-full border rounded-2xl p-2.5 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Description (Optional)</label>
          <textarea x-model="newTaskData.description" rows="2" placeholder="Actionable details..." class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Priority</label>
            <select x-model="newTaskData.priority" class="w-full border rounded-2xl p-2.5 bg-slate-50 font-semibold focus:outline-none">
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status</label>
            <select x-model="newTaskData.status" class="w-full border rounded-2xl p-2.5 bg-slate-50 font-semibold focus:outline-none">
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Due Date</label>
          <input type="date" x-model="newTaskData.due_date" class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none">
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t">
          <button type="button" x-on:click="newTaskModalOpen=false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-sm transition">Create Task</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function tasksAndMessagesPage() {
  return {
    activeTab: 'tasks',
    taskDisplayMode: 'table',
    tasks: [],
    messages: [],
    searchQ: '',
    filterStatus: '',
    filterPriority: '',
    filterOverdue: false,

    msgSearchQ: '',
    msgFilterType: '',
    msgFilterStatus: '',
    msgFilterCategory: '',

    currentMessage: null,
    editingTask: null,
    newTaskModalOpen: false,
    newTaskData: { title: '', description: '', priority: 'Medium', status: 'Pending', due_date: '' },

    async initPage() {
      // Check URL parameters for view or open message
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('view') === 'messages') this.activeTab = 'messages';
      if (urlParams.get('action') === 'new_task') this.newTaskModalOpen = true;

      await this.loadTasks();

      if (this.activeTab === 'messages') {
        await this.loadMessages();
      } else {
        setTimeout(() => this.loadMessages(), 50);
      }

      const openId = urlParams.get('open');
      if (openId) {
        this.inspectMessage(openId);
      }
    },

    async loadTasks() {
      const p = new URLSearchParams();
      if (this.searchQ) p.set('q', this.searchQ);
      if (this.filterStatus) p.set('status', this.filterStatus);
      if (this.filterPriority) p.set('priority', this.filterPriority);
      if (this.filterOverdue) p.set('overdue', '1');
      p.set('per_page', '100');

      try {
        const res = await fetch('/api/tasks?' + p.toString(), { headers: { 'Accept': 'application/json' } });
        const d = await res.json();
        this.tasks = d.data || [];
      } catch (e) {
        console.error(e);
      }
    },

    async loadMessages() {
      const p = new URLSearchParams();
      if (this.msgSearchQ) p.set('q', this.msgSearchQ);
      if (this.msgFilterType) p.set('type', this.msgFilterType);
      if (this.msgFilterStatus) p.set('analysis_status', this.msgFilterStatus);
      if (this.msgFilterCategory) p.set('category', this.msgFilterCategory);
      p.set('per_page', '100');

      try {
        const res = await fetch('/api/messages?' + p.toString(), { headers: { 'Accept': 'application/json' } });
        const d = await res.json();
        this.messages = d.data || [];
      } catch (e) {
        console.error(e);
      }
    },

    async quickUpdate(task, patch) {
      try {
        await fetch('/api/tasks/' + task.id, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: JSON.stringify(patch)
        });
        await this.loadTasks();
      } catch (e) {
        alert(e.message);
      }
    },

    openEditModal(task) {
      this.editingTask = {
        id: task.id,
        title: task.title,
        description: task.description || '',
        priority: task.priority,
        status: task.status,
        due_date: task.due_date || ''
      };
    },

    async saveTaskEdit() {
      try {
        const { id, title, description, priority, status, due_date } = this.editingTask;
        await fetch('/api/tasks/' + id, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: JSON.stringify({
            title,
            description: description || null,
            priority,
            status,
            due_date: due_date || null
          })
        });
        this.editingTask = null;
        await this.loadTasks();
      } catch (e) {
        alert(e.message);
      }
    },

    openNewTaskModal() {
      this.newTaskData = { title: '', description: '', priority: 'Medium', status: 'Pending', due_date: '' };
      this.newTaskModalOpen = true;
    },

    async submitCreateTask() {
      try {
        await fetch('/api/tasks', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: JSON.stringify(this.newTaskData)
        });
        this.newTaskModalOpen = false;
        await this.loadTasks();
      } catch (e) {
        alert(e.message);
      }
    },

    async deleteTask(task) {
      if (!confirm(`Delete task "${task.title}"?`)) return;
      try {
        await fetch('/api/tasks/' + task.id, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          }
        });
        await this.loadTasks();
      } catch (e) {
        alert(e.message);
      }
    },

    async inspectMessage(id) {
      if (!id) return;
      try {
        const res = await fetch('/api/messages/' + id, { headers: { 'Accept': 'application/json' } });
        this.currentMessage = await res.json();
      } catch (e) {
        console.error(e);
      }
    },

    async analyzeSingleMessage(m, force = false) {
      try {
        const res = await fetch(`/api/messages/${m.id}/analyze${force ? '?force=1' : ''}`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          }
        });
        const updated = await res.json();
        if (this.currentMessage && this.currentMessage.id === m.id) {
          this.currentMessage = updated;
        }
        await this.loadTasks();

      if (this.activeTab === 'messages') {
        await this.loadMessages();
      } else {
        setTimeout(() => this.loadMessages(), 50);
      }
      } catch (e) {
        alert(e.message);
      }
    },

    copySuggestedReply(text) {
      if (!text) return;
      navigator.clipboard.writeText(text);
      alert('Suggested reply copied to clipboard!');
    },

    isOverdue(t) {
      if (!t.due_date || t.status === 'Completed') return false;
      const today = new Date().toISOString().slice(0, 10);
      return t.due_date < today;
    }
  };
}
</script>
@endpush

