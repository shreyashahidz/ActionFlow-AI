<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(Request $r)
    {
        $q = Task::with('message:id,external_id,subject,sender_name');

        if ($s = trim($r->query('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('message', function ($m) use ($s) {
                        $m->where('subject', 'like', "%{$s}%")
                            ->orWhere('sender_name', 'like', "%{$s}%");
                    });
            });
        }

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        if ($r->filled('priority')) {
            $q->where('priority', $r->priority);
        }

        if ($r->boolean('overdue')) {
            $q->where('status', '!=', 'Completed')->whereNotNull('due_date')->whereDate('due_date', '<', today());
        }

        $q->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderByDesc('id');

        $perPage = min(max($r->integer('per_page', 50), 1), 200);

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['sometimes', 'in:High,Medium,Low'],
            'status'      => ['sometimes', 'in:Pending,In Progress,Completed'],
            'due_date'    => ['nullable', 'date_format:Y-m-d'],
            'message_id'  => ['nullable', 'exists:messages,id'],
        ]);

        $task = Task::create(array_merge($data, [
            'priority' => $data['priority'] ?? 'Medium',
            'status'   => $data['status'] ?? 'Pending',
            'source'   => 'manual',
        ]));

        return response()->json($task->load('message'), 201);
    }

    public function update(Request $r, Task $task)
    {
        $data = $r->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority'    => ['sometimes', 'in:High,Medium,Low'],
            'status'      => ['sometimes', 'in:Pending,In Progress,Completed'],
            'due_date'    => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $task->update($data);

        return response()->json($task->fresh('message'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }

    public function export(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="actionflow_tasks_' . date('Y-m-d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Title',
                'Description',
                'Priority',
                'Status',
                'Due Date',
                'Message ID',
                'Message Subject',
                'Created At',
            ]);

            $sanitize = function (?string $val): string {
                $val = (string) $val;
                if (preg_match('/^[=\+\-@\t\r]/', $val)) {
                    return "'" . $val;
                }
                return $val;
            };

            Task::with('message:id,subject')->chunk(200, function ($tasks) use ($handle, $sanitize) {
                foreach ($tasks as $t) {
                    fputcsv($handle, [
                        $t->id,
                        $sanitize($t->title),
                        $sanitize($t->description),
                        $t->priority,
                        $t->status,
                        $t->due_date ? $t->due_date->format('Y-m-d') : '',
                        $t->message_id ?? '',
                        $sanitize($t->message?->subject ?? ''),
                        $t->created_at ? $t->created_at->toIso8601String() : '',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, 'actionflow_tasks_' . date('Y-m-d_His') . '.csv', $headers);
    }
}

