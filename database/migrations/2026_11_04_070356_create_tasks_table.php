<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->nullable()
              ->constrained()->nullOnDelete();            // related original message
            $t->string('title');
            $t->text('description')->nullable();
            $t->enum('priority', ['High','Medium','Low'])->default('Medium');
            $t->date('due_date')->nullable();
            $t->enum('status', ['Pending','In Progress','Completed'])
              ->default('Pending');
            $t->enum('source', ['ai','manual'])->default('ai');
            $t->unsignedBigInteger('duplicate_of')->nullable(); // bonus
            $t->timestamps();
            $t->index(['status','priority']);
            $t->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
