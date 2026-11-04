<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $r)
    {
        $q = Message::with('analysis:id,message_id,summary,category,priority,requires_response,suggested_reply')
            ->withCount('tasks');

        if ($s = trim($r->query('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('subject', 'like', "%{$s}%")
                    ->orWhere('sender_name', 'like', "%{$s}%")
                    ->orWhere('sender_email', 'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%")
                    ->orWhere('external_id', 'like', "%{$s}%");
            });
        }

        if ($r->filled('type')) {
            $q->where('type', $r->type);
        }

        if ($r->filled('analysis_status')) {
            $q->where('analysis_status', $r->analysis_status);
        }

        if ($r->filled('category')) {
            $q->whereHas('analysis', function ($a) use ($r) {
                $a->where('category', $r->category);
            });
        }

        if ($r->has('requires_response') && $r->requires_response !== '') {
            $q->whereHas('analysis', function ($a) use ($r) {
                $a->where('requires_response', $r->boolean('requires_response'));
            });
        }

        $q->orderByDesc('sent_at');

        $perPage = min(max($r->integer('per_page', 50), 1), 200);

        return response()->json($q->paginate($perPage));
    }

    public function show(Message $message)
    {
        return response()->json($message->load('analysis', 'tasks'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'type'         => ['required', 'in:email,message'],
            'sender_name'  => ['required', 'string', 'max:255'],
            'sender_email' => ['required', 'email', 'max:255'],
            'recipient'    => ['required', 'string', 'max:255'],
            'subject'      => ['nullable', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'date'         => ['nullable', 'date'],
            'attachments'  => ['nullable', 'array'],
        ]);

        $externalId = 'MAN-' . Str::upper(Str::random(6));
        while (Message::where('external_id', $externalId)->exists()) {
            $externalId = 'MAN-' . Str::upper(Str::random(6));
        }

        $message = Message::create([
            'external_id'     => $externalId,
            'type'            => $data['type'],
            'sender_name'     => $data['sender_name'],
            'sender_email'    => $data['sender_email'],
            'recipient'       => $data['recipient'],
            'subject'         => $data['subject'] ?? ($data['type'] === 'message' ? '' : 'No Subject'),
            'sent_at'         => !empty($data['date']) ? Carbon::parse($data['date']) : Carbon::now(),
            'content'         => $data['content'],
            'attachments'     => $data['attachments'] ?? [],
            'analysis_status' => 'pending',
        ]);

        return response()->json($message->load('analysis', 'tasks'), 201);
    }
}

