<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_analyses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->unique()
              ->constrained()->cascadeOnDelete();
            $t->text('summary');
            $t->string('category', 60);
            $t->string('intent', 255);
            $t->enum('priority', ['High','Medium','Low']);
            $t->boolean('requires_response')->default(false);
            $t->text('suggested_reply')->nullable();
            $t->json('raw_response')->nullable();
            $t->string('model', 60)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_analyses');
    }
};
