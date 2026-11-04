<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\AnalysisService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function analyze(Message $message, AnalysisService $svc, Request $r)
    {
        try {
            $m = $svc->run($message, $r->boolean('force'));
            return response()->json($m->load('analysis', 'tasks'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}

