<?php

namespace App\Http\Controllers;

use App\Models\{Message, MessageAnalysis, Task};
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function tasks()
    {
        return view('tasks-and-messages');
    }

    public function import()
    {
        return view('import-automation');
    }

    public function stats()
    {
        $stats = Cache::remember('dashboard_stats', 3, function () {
            return [
                'total_messages'     => Message::count(),
                'analyzed_messages'  => Message::where('analysis_status', 'analyzed')->count(),
                'pending_analysis'   => Message::where('analysis_status', 'pending')->count(),
                'failed_analysis'    => Message::where('analysis_status', 'failed')->count(),
                'pending_tasks'      => Task::where('status', 'Pending')->count(),
                'in_progress_tasks'  => Task::where('status', 'In Progress')->count(),
                'completed_tasks'    => Task::where('status', 'Completed')->count(),
                'high_priority_open' => Task::where('priority', 'High')->where('status', '!=', 'Completed')->count(),
                'overdue_tasks'      => Task::where('status', '!=', 'Completed')
                                            ->whereNotNull('due_date')->whereDate('due_date', '<', today())->count(),
                'needs_reply'        => MessageAnalysis::where('requires_response', true)->count(),
            ];
        });

        return response()->json($stats);
    }

    public function analytics()
    {
        $data = Cache::remember('dashboard_analytics', 3, function () {
            $categories = MessageAnalysis::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category');

            $taskPriorities = Task::selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority');

            $taskStatuses = Task::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $messagePriorities = MessageAnalysis::selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority');

            $dailyInflow = Message::selectRaw('DATE(sent_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->pluck('count', 'date');

            $urgentTasks = Task::with('message:id,external_id,subject,sender_name')
                ->where('status', '!=', 'Completed')
                ->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->limit(6)
                ->get();

            $recentMessages = Message::with('analysis:id,message_id,category,priority,summary,requires_response')
                ->orderByDesc('sent_at')
                ->limit(5)
                ->get();

            $totalTasks = Task::count();
            $completedTasks = Task::where('status', 'Completed')->count();
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $totalAnalyzed = MessageAnalysis::count();
            $replyNeeded = MessageAnalysis::where('requires_response', true)->count();
            $replyRate = $totalAnalyzed > 0 ? round(($replyNeeded / $totalAnalyzed) * 100) : 0;

            return [
                'categories'         => $categories,
                'task_priorities'    => $taskPriorities,
                'task_statuses'      => $taskStatuses,
                'message_priorities' => $messagePriorities,
                'daily_inflow'       => $dailyInflow,
                'urgent_tasks'       => $urgentTasks,
                'recent_messages'    => $recentMessages,
                'completion_rate'    => $completionRate,
                'reply_rate'         => $replyRate,
            ];
        });

        return response()->json($data);
    }

    public function briefing(GeminiService $gemini)
    {
        $openTasks = Task::with('message:id,external_id,sender_name,subject')
            ->where('status', '!=', 'Completed')
            ->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit(10)
            ->get()
            ->toArray();

        try {
            $summary = $gemini->generateBriefing($openTasks);
            return response()->json(['briefing' => $summary]);
        } catch (\Throwable $e) {
            return response()->json([
                'briefing' => "Could not generate briefing: " . $e->getMessage()
            ], 500);
        }
    }
}
