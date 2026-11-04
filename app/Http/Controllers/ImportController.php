<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Services\MessageImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        return response()->json(ImportLog::latest()->take(20)->get());
    }

    public function store(Request $request, MessageImportService $svc)
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'max:5120'], // 5 MB
            ]);
            $file = $request->file('file');
            if (strtolower($file->getClientOriginalExtension()) !== 'json') {
                return response()->json(['message' => 'Only .json files are allowed.'], 422);
            }

            try {
                $result = $svc->importFromString(file_get_contents($file->getRealPath()), $file->getClientOriginalName());
                return response()->json($result);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        if ($request->filled('json_content')) {
            try {
                $result = $svc->importFromString($request->input('json_content'), 'raw_json_input.json');
                return response()->json($result);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        return response()->json(['message' => 'Please upload a JSON file or provide JSON content.'], 422);
    }
}

