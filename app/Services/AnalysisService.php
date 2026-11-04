<?php

namespace App\Services;

use App\Models\{Message, MessageAnalysis, Task};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\{Carbon, Str};

class AnalysisService
{
    public function __construct(private GeminiService $gemini) {}

    public function run(Message $m, bool $force = false): Message
    {
        if ($m->analysis_status === 'analyzed' && !$force) {
            return $m;
        }

        try {
            $raw = $this->gemini->analyze([
                'sent_at'      => $m->sent_at->format('Y-m-d H:i'),
                'weekday'      => $m->sent_at->format('l'),
                'type'         => $m->type,
                'sender_name'  => $m->sender_name,
                'sender_email' => $m->sender_email,
                'recipient'    => $m->recipient,
                'subject'      => $m->subject ?? '',
                'attachments'  => $m->attachments ?? [],
                'content'      => $m->content,
            ]);
            $clean = $this->normalize($raw, $m);

            DB::transaction(function () use ($m, $clean, $raw, $force) {
                MessageAnalysis::updateOrCreate(['message_id' => $m->id], [
                    'summary'           => $clean['summary'],
                    'category'          => $clean['category'],
                    'intent'            => $clean['intent'],
                    'priority'          => $clean['priority'],
                    'requires_response' => $clean['requires_response'],
                    'suggested_reply'   => $clean['suggested_reply'],
                    'raw_response'      => $raw,
                    'model'             => config('services.gemini.model'),
                ]);

                if ($force) {   // re-analysis: replace untouched AI tasks only
                    Task::where('message_id', $m->id)->where('source', 'ai')
                        ->where('status', 'Pending')->delete();
                }
                foreach ($clean['tasks'] as $t) {
                    Task::create($t + ['message_id' => $m->id, 'source' => 'ai', 'status' => 'Pending']);
                }
                $m->update(['analysis_status' => 'analyzed', 'analysis_error' => null]);
            });
        } catch (\Throwable $e) {
            $m->update(['analysis_status' => 'failed', 'analysis_error' => Str::limit($e->getMessage(), 500)]);
            throw $e;
        }

        return $m->fresh(['analysis', 'tasks']);
    }

    private function normalize(array $d, Message $m): array
    {
        $prio = fn($p) => in_array($p, ['High', 'Medium', 'Low'], true) ? $p : 'Medium';
        $cat  = in_array($d['category'] ?? '', PromptBuilder::CATEGORIES, true) ? $d['category'] : 'Other';
        $needsReply = (bool)($d['requires_response'] ?? false);

        $tasks = [];
        $noTaskCats = ['Informational', 'No Action Needed'];
        if (!in_array($cat, $noTaskCats, true)) {
            foreach (($d['tasks'] ?? []) as $t) {
                $title = trim($t['title'] ?? '');
                if ($title === '') {
                    continue;
                }

                // ---- anti-hallucination guard for due dates ----
                $due = null;
                $dueText = trim((string)($t['due_text'] ?? ''));
                if (!empty($t['due_date']) && $dueText !== ''
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t['due_date'])
                    && Str::contains(Str::lower($m->content . ' ' . $m->subject), Str::lower($dueText))) {
                    try {
                        $due = Carbon::createFromFormat('Y-m-d', $t['due_date'])->toDateString();
                    } catch (\Throwable) {
                        $due = null;
                    }
                }

                $dup = Task::whereNotIn('status', ['Completed'])
                    ->get(['id', 'title'])->first(function ($x) use ($title) {
                        similar_text(Str::lower($x->title), Str::lower($title), $pct);
                        return $pct >= 85;
                    });

                $tasks[] = [
                    'title'        => Str::limit($title, 250, ''),
                    'description'  => trim($t['description'] ?? '') ?: null,
                    'priority'     => $prio($t['priority'] ?? 'Medium'),
                    'due_date'     => $due,
                    'duplicate_of' => $dup?->id,
                ];
            }
        }

        return [
            'summary'           => trim($d['summary'] ?? '') ?: 'No summary available.',
            'category'          => $cat,
            'intent'            => trim($d['intent'] ?? '') ?: 'Not specified',
            'priority'          => $prio($d['priority'] ?? 'Medium'),
            'requires_response' => $needsReply,
            'suggested_reply'   => $needsReply ? trim($d['suggested_reply'] ?? '') : '',
            'tasks'             => $tasks,
        ];
    }
}

