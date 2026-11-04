<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Message, Task, ImportLog};
use App\Services\MessageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActionFlowTest extends TestCase
{
    use RefreshDatabase;
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('ActionFlow AI');
    }

    public function test_stats_api_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/stats');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_messages',
                'analyzed_messages',
                'pending_analysis',
                'failed_analysis',
                'pending_tasks',
                'in_progress_tasks',
                'completed_tasks',
                'high_priority_open',
                'overdue_tasks',
                'needs_reply',
            ]);
    }

    public function test_message_import_validation(): void
    {
        $service = new MessageImportService();

        // 1. Invalid JSON
        $this->expectException(\InvalidArgumentException::class);
        $service->importFromString('not a json');
    }

    public function test_manual_message_entry(): void
    {
        $response = $this->postJson('/api/messages', [
            'type'         => 'email',
            'sender_name'  => 'Test User',
            'sender_email' => 'test@example.com',
            'recipient'    => 'team@example.com',
            'subject'      => 'Test Subject',
            'content'      => 'Test Content for ActionFlow',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('sender_name', 'Test User')
            ->assertJsonPath('analysis_status', 'pending');
    }

    public function test_task_creation_and_update(): void
    {
        $createRes = $this->postJson('/api/tasks', [
            'title'       => 'Test Implementation Task',
            'description' => 'Detailed test instructions',
            'priority'    => 'High',
            'status'      => 'Pending',
            'due_date'    => '2026-11-10',
        ]);

        $createRes->assertStatus(201);
        $taskId = $createRes->json('id');

        $updateRes = $this->putJson("/api/tasks/{$taskId}", [
            'status'   => 'In Progress',
            'priority' => 'Medium',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('status', 'In Progress')
            ->assertJsonPath('priority', 'Medium');

        $deleteRes = $this->deleteJson("/api/tasks/{$taskId}");
        $deleteRes->assertStatus(204);
    }

    public function test_task_csv_export(): void
    {
        $response = $this->get('/api/tasks/export');
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_tasks_page_loads_successfully(): void
    {
        $response = $this->get('/tasks');
        $response->assertStatus(200);
        $response->assertSee('Tasks Management');
    }

    public function test_import_page_loads_successfully(): void
    {
        $response = $this->get('/import');
        $response->assertStatus(200);
        $response->assertSee('Import &amp; Automation', false);
    }

    public function test_analytics_api_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/analytics');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'categories',
                'task_priorities',
                'task_statuses',
                'message_priorities',
                'daily_inflow',
                'urgent_tasks',
                'recent_messages',
                'completion_rate',
                'reply_rate',
            ]);
    }
}
