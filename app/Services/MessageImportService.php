<?php

namespace App\Services;

use App\Models\{Message, ImportLog};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Carbon;

class MessageImportService
{
    private const ISO = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

    public function importFromString(string $json, ?string $filename = null): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new \InvalidArgumentException('Root object must contain a "messages" array.');
        }
        if (count($data['messages']) === 0) {
            throw new \InvalidArgumentException('"messages" array is empty.');
        }

        $result = ['total'=>count($data['messages']),'imported'=>0,'skipped'=>0,'failed'=>0,'errors'=>[]];
        $seen = [];

        foreach ($data['messages'] as $i => $m) {
            $label = is_array($m) && isset($m['id']) ? (string)$m['id'] : "index $i";

            if (!is_array($m)) {
                $result['failed']++; $result['errors'][] = "$label: not an object"; continue;
            }

            $v = Validator::make($m, [
                'id'            => ['required','string','max:50'],
                'type'          => ['required','in:email,message'],
                'sender'        => ['required','array'],
                'sender.name'   => ['required','string','max:255'],
                'sender.email'  => ['required','email','max:255'],
                'recipient'     => ['required','string','max:255'],
                'subject'       => ['present','nullable','string','max:255'],
                'date'          => ['required','string','regex:'.self::ISO],
                'content'       => ['required','string'],
                'attachments'   => ['present','array'],
            ]);

            if ($v->fails()) {
                $result['failed']++;
                $result['errors'][] = "$label: " . implode('; ', $v->errors()->all());
                continue;
            }

            if (isset($seen[$m['id']])) {
                $result['failed']++;
                $result['errors'][] = "$label: duplicate id inside the file";
                continue;
            }
            $seen[$m['id']] = true;

            if (Message::where('external_id', $m['id'])->exists()) {
                $result['skipped']++; continue;       // already imported earlier
            }

            try {
                $known = ['id','type','sender','recipient','subject','date','content','attachments'];
                Message::create([
                    'external_id'  => $m['id'],
                    'type'         => $m['type'],
                    'sender_name'  => $m['sender']['name'],
                    'sender_email' => $m['sender']['email'],
                    'recipient'    => $m['recipient'],
                    'subject'      => $m['subject'],
                    'sent_at'      => Carbon::parse($m['date']),
                    'content'      => $m['content'],
                    'attachments'  => $m['attachments'],
                    'extra'        => array_diff_key($m, array_flip($known)) ?: null,
                ]);
                $result['imported']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = "$label: " . $e->getMessage();
            }
        }

        ImportLog::create([
            'filename' => $filename,
            'total'    => $result['total'],
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
            'failed'   => $result['failed'],
            'errors'   => $result['errors'] ?: null,
        ]);

        return $result;
    }
}

