@extends('layouts.app')

@section('title', 'Import & Automation')
@section('page_name', 'Import & Automation Hub')

@section('content')
<div x-data="importAutomationPage()" x-init="initImportPage()" class="space-y-6">

  <!-- PAGE HEADER -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Import &amp; Automation Hub</h2>
        <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-100">Batch Processing</span>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Upload JSON communication datasets, run automated batch triage with Gemini, and simulate incoming client channels.</p>
    </div>

    <!-- Quick link to view results -->
    <a href="/tasks" class="inline-flex items-center gap-2 text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-2xl shadow-sm transition">
      <span>View Extracted Tasks &rarr;</span>
    </a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- CARD 1: JSON DATASET IMPORT ZONE -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 space-y-5">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-sm">1</div>
        <div>
          <h3 class="font-bold text-slate-900 text-base">Upload JSON Dataset</h3>
          <p class="text-xs text-slate-500">Supports <code class="text-indigo-600 font-mono">sample_messages.json</code> with schema validation</p>
        </div>
      </div>

      <!-- Upload Dropzone -->
      <div class="border-2 border-dashed border-slate-300 rounded-3xl p-8 text-center hover:border-indigo-400 transition bg-slate-50/60 flex flex-col items-center justify-center">
        <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-500 mb-3 border border-slate-200">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
        </div>
        <p class="text-sm font-bold text-slate-800">Select or drop your JSON file here</p>
        <p class="text-xs text-slate-500 mt-1 max-w-xs">Maximum 5 MB. Validates ID, sender, recipient, ISO 8601 timestamps, and skips duplicates.</p>

        <label class="mt-4 cursor-pointer inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-4 py-2.5 rounded-2xl shadow-sm transition">
          <span>Choose File</span>
          <input type="file" accept=".json" x-on:change="handleFileUpload($event)" class="hidden">
        </label>
      </div>

      <!-- IMPORT RESULT SUMMARY BANNER -->
      <template x-if="importResult">
        <div class="p-4 rounded-2xl border transition" :class="importResult.failed > 0 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900'">
          <div class="font-extrabold text-xs flex items-center justify-between">
            <span>Import Completed Successfully</span>
            <span class="font-mono bg-white px-2 py-0.5 rounded-lg border text-[11px]" x-text="`${importResult.imported} inserted, ${importResult.skipped} skipped`"></span>
          </div>

          <div class="grid grid-cols-4 gap-2 text-center text-xs mt-3">
            <div class="bg-white/80 p-2.5 rounded-xl border">Total: <strong x-text="importResult.total"></strong></div>
            <div class="bg-white/80 p-2.5 rounded-xl border text-emerald-700">Imported: <strong x-text="importResult.imported"></strong></div>
            <div class="bg-white/80 p-2.5 rounded-xl border text-amber-700">Skipped: <strong x-text="importResult.skipped"></strong></div>
            <div class="bg-white/80 p-2.5 rounded-xl border text-rose-700">Failed: <strong x-text="importResult.failed"></strong></div>
          </div>

          <template x-if="importResult.errors && importResult.errors.length">
            <div class="mt-3 text-xs bg-white/95 p-3 rounded-xl border border-rose-200 text-rose-700 max-h-32 overflow-y-auto">
              <div class="font-bold mb-1">Errors encountered during validation:</div>
              <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                <template x-for="err in importResult.errors" :key="err">
                  <li x-text="err"></li>
                </template>
              </ul>
            </div>
          </template>
        </div>
      </template>

      <!-- Raw JSON Input option -->
      <div class="pt-2">
        <details class="text-xs text-slate-500 cursor-pointer">
          <summary class="font-semibold text-indigo-600 hover:underline">Or paste raw JSON content</summary>
          <div class="mt-3 space-y-2">
            <textarea x-model="rawJsonInput" rows="4" placeholder='{"messages": [{"id": "MSG999", "type": "email", ...}]}' class="w-full border rounded-2xl p-3 text-xs font-mono bg-slate-50 focus:bg-white focus:outline-none"></textarea>
            <button x-on:click="submitRawJson()" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2 rounded-xl">
              Import from Text
            </button>
          </div>
        </details>
      </div>
    </div>

    <!-- CARD 2: BATCH AI AUTOMATION RUNNER -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 space-y-5 flex flex-col justify-between">
      <div class="space-y-4">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-black text-sm">2</div>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Gemini AI Batch Triage</h3>
            <p class="text-xs text-slate-500">Autonomous processing with structured outputs and rate-limit guardrails</p>
          </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-50/70 to-slate-50 rounded-2xl p-5 border border-indigo-100 space-y-3">
          <div class="flex items-center justify-between text-xs font-bold text-slate-800">
            <span>Current Inbox Status</span>
            <span class="text-indigo-600" x-text="`${pendingCount} pending analysis`"></span>
          </div>

          <p class="text-xs text-slate-600 leading-relaxed">
            The batch runner sequentially executes Gemini 2.5 Flash on every pending message. Each response is validated server-side to prevent deadline hallucinations, and calls are spaced (~6.5s) to guarantee zero HTTP 429 rate limit errors.
          </p>

          <!-- Action Button -->
          <button x-on:click="runBatchAnalysis()" :disabled="batchRunning || pendingCount === 0"
            class="w-full py-3 px-4 rounded-2xl font-bold text-xs text-white shadow-sm transition flex items-center justify-center gap-2"
            :class="batchRunning || pendingCount === 0 ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'bg-indigo-600 hover:bg-indigo-700'">
            <svg class="w-4 h-4 text-amber-300" :class="batchRunning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span x-text="batchRunning ? 'Batch Analysis In Progress...' : (pendingCount > 0 ? `Analyze All ${pendingCount} Pending Messages` : 'All Messages Analyzed')"></span>
          </button>
        </div>

        <!-- Live Progress Indicator -->
        <div x-show="batchProgress" class="space-y-2 bg-slate-50 p-4 rounded-2xl border border-slate-200/80" x-cloak>
          <div class="flex items-center justify-between text-xs font-bold text-slate-700">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-indigo-600 animate-ping"></span>
              <span x-text="currentAnalyzingId ? `Processing: ${currentAnalyzingId}` : 'Running batch...'"></span>
            </span>
            <span x-text="`${batchProgress?.done || 0} / ${batchProgress?.total || 0}`"></span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="`width: ${batchProgress ? Math.round((batchProgress.done / batchProgress.total) * 100) : 0}%`"></div>
          </div>
          <div class="text-[10px] text-slate-400 flex items-center justify-between">
            <span>Paced for free-tier rate limits</span>
            <span x-text="rateLimitCountdown > 0 ? `Next request in ${rateLimitCountdown}s...` : 'Calling API...'"></span>
          </div>
        </div>
      </div>

      <div class="text-[11px] text-slate-400 italic pt-2 border-t border-slate-100">
        💡 Model: <strong class="text-slate-700">gemini-2.5-flash</strong> • Enforces strict JSON schema &amp; zero invented due dates.
      </div>
    </div>

  </div>

  <!-- CARD 3: MANUAL MESSAGE SIMULATOR (Bonus 1) -->
  <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 space-y-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-sm">3</div>
      <div>
        <h3 class="font-bold text-slate-900 text-base">Manual Message Entry &amp; Channel Simulator</h3>
        <p class="text-xs text-slate-500">Inject incoming emails or WhatsApp chats directly into ActionFlow AI</p>
      </div>
    </div>

    <form x-on:submit.prevent="submitManualMessage()" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
      <div>
        <label class="block font-bold text-slate-700 mb-1">Communication Type</label>
        <select x-model="manualForm.type" class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white font-semibold focus:outline-none">
          <option value="email">Email</option>
          <option value="message">Chat / WhatsApp Message</option>
        </select>
      </div>

      <div>
        <label class="block font-bold text-slate-700 mb-1">Recipient Address</label>
        <input type="text" x-model="manualForm.recipient" required class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none" placeholder="team@example.com">
      </div>

      <div>
        <label class="block font-bold text-slate-700 mb-1">Sender Full Name</label>
        <input type="text" x-model="manualForm.sender_name" required class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none" placeholder="e.g. Deepak Joshi">
      </div>

      <div>
        <label class="block font-bold text-slate-700 mb-1">Sender Email</label>
        <input type="email" x-model="manualForm.sender_email" required class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none" placeholder="deepak@client.example.com">
      </div>

      <div class="md:col-span-2">
        <label class="block font-bold text-slate-700 mb-1">Subject (optional for chat messages)</label>
        <input type="text" x-model="manualForm.subject" class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none" placeholder="e.g. Critical database latency issue">
      </div>

      <div class="md:col-span-2">
        <label class="block font-bold text-slate-700 mb-1">Message Content</label>
        <textarea x-model="manualForm.content" rows="3" required class="w-full border rounded-2xl p-2.5 bg-slate-50 focus:bg-white focus:outline-none leading-relaxed" placeholder="Write or paste incoming communication here..."></textarea>
      </div>

      <div class="md:col-span-2 flex justify-end">
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-2xl shadow-sm transition">
          Inject &amp; Queue Message
        </button>
      </div>
    </form>
  </div>

  <!-- CARD 4: AUDIT LOG & IMPORT HISTORY -->
  <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-slate-900 text-base">Import History &amp; Audit Logs</h3>
      <span class="text-xs text-slate-400 font-medium" x-text="`${auditLogs.length} previous logs`"></span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
          <tr>
            <th class="py-3 px-4">Date &amp; Time</th>
            <th class="py-3 px-4">Filename</th>
            <th class="py-3 px-4">Total Detected</th>
            <th class="py-3 px-4">Imported</th>
            <th class="py-3 px-4">Skipped (Duplicate)</th>
            <th class="py-3 px-4">Failed</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="l in auditLogs" :key="l.id">
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3 px-4 whitespace-nowrap text-slate-500 font-mono text-[11px]" x-text="new Date(l.created_at).toLocaleString()"></td>
              <td class="py-3 px-4 font-bold text-slate-800" x-text="l.filename || 'Direct Upload'"></td>
              <td class="py-3 px-4 font-semibold text-slate-700" x-text="l.total"></td>
              <td class="py-3 px-4 font-bold text-emerald-600" x-text="l.imported"></td>
              <td class="py-3 px-4 font-bold text-amber-600" x-text="l.skipped"></td>
              <td class="py-3 px-4 font-bold text-rose-600" x-text="l.failed"></td>
            </tr>
          </template>

          <tr x-show="!auditLogs.length">
            <td colspan="6" class="py-8 text-center text-slate-400 italic">No import history recorded yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function importAutomationPage() {
  return {
    pendingCount: 0,
    batchRunning: false,
    batchProgress: null,
    currentAnalyzingId: null,
    rateLimitCountdown: 0,
    importResult: null,
    rawJsonInput: '',
    auditLogs: [],
    manualForm: {
      type: 'email',
      recipient: 'team@example.com',
      sender_name: '',
      sender_email: '',
      subject: '',
      content: ''
    },

    async initImportPage() {
      await Promise.all([this.loadPendingCount(), this.loadAuditLogs()]);
    },

    async loadPendingCount() {
      try {
        const res = await fetch('/api/stats', { headers: { 'Accept': 'application/json' } });
        const d = await res.json();
        this.pendingCount = d.pending_analysis || 0;
      } catch (e) {
        console.error(e);
      }
    },

    async loadAuditLogs() {
      try {
        const res = await fetch('/api/import-logs', { headers: { 'Accept': 'application/json' } });
        this.auditLogs = await res.json();
      } catch (e) {
        console.error(e);
      }
    },

    async handleFileUpload(ev) {
      const file = ev.target.files[0];
      if (!file) return;

      const fd = new FormData();
      fd.append('file', file);

      try {
        const res = await fetch('/api/import', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: fd
        });
        this.importResult = await res.json();
        await Promise.all([this.loadPendingCount(), this.loadAuditLogs()]);
      } catch (e) {
        alert(e.message);
      } finally {
        ev.target.value = '';
      }
    },

    async submitRawJson() {
      if (!this.rawJsonInput.trim()) return;
      try {
        const res = await fetch('/api/import', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: JSON.stringify({ json_content: this.rawJsonInput })
        });
        this.importResult = await res.json();
        this.rawJsonInput = '';
        await Promise.all([this.loadPendingCount(), this.loadAuditLogs()]);
      } catch (e) {
        alert(e.message);
      }
    },

    async submitManualMessage() {
      try {
        await fetch('/api/messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
          },
          body: JSON.stringify(this.manualForm)
        });
        this.manualForm = {
          type: 'email',
          recipient: 'team@example.com',
          sender_name: '',
          sender_email: '',
          subject: '',
          content: ''
        };
        alert('Manual message successfully queued for analysis!');
        await this.loadPendingCount();
      } catch (e) {
        alert(e.message);
      }
    },

    async runBatchAnalysis() {
      this.batchRunning = true;
      try {
        const res = await fetch('/api/messages?analysis_status=pending&per_page=200', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const pending = data.data || [];
        if (!pending.length) {
          alert('No pending messages to analyze.');
          this.batchRunning = false;
          return;
        }

        for (let i = 0; i < pending.length; i++) {
          this.currentAnalyzingId = pending[i].external_id;
          this.batchProgress = { done: i, total: pending.length };

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

          await this.loadPendingCount();

          // Spacing for free-tier rate limit
          if (i < pending.length - 1) {
            for (let sec = 6; sec > 0; sec--) {
              this.rateLimitCountdown = sec;
              await new Promise(r => setTimeout(r, 1000));
            }
            this.rateLimitCountdown = 0;
          }
        }
        alert('Batch analysis finished successfully!');
      } catch (err) {
        alert(err.message);
      } finally {
        this.batchProgress = null;
        this.currentAnalyzingId = null;
        this.batchRunning = false;
      }
    }
  };
}
</script>
@endpush

