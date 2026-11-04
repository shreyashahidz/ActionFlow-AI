<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $t) {
            $t->id();
            $t->string('filename')->nullable();
            $t->unsignedInteger('total')->default(0);
            $t->unsignedInteger('imported')->default(0);
            $t->unsignedInteger('skipped')->default(0);
            $t->unsignedInteger('failed')->default(0);
            $t->json('errors')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
