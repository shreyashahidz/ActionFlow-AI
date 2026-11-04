<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->string('external_id', 50)->unique();      // "MSG001"
            $t->enum('type', ['email','message']);
            $t->string('sender_name');
            $t->string('sender_email');
            $t->string('recipient');
            $t->string('subject')->nullable();
            $t->dateTime('sent_at');
            $t->longText('content');
            $t->json('attachments')->nullable();
            $t->json('extra')->nullable();                // any extra JSON fields
            $t->enum('analysis_status', ['pending','analyzed','failed'])
              ->default('pending');
            $t->text('analysis_error')->nullable();
            $t->timestamps();
            $t->index('analysis_status');
            $t->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
