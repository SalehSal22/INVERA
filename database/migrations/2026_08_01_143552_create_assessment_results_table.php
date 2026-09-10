<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('branch');          // e.g., 'loss_of_interest'
            $table->string('instrument');      // e.g., 'SHAPS'
            $table->integer('level_reached');  // e.g., 3
            $table->integer('pct');            // e.g., 62 (percentage score)
            $table->string('severity');        // e.g., 'Moderate'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
