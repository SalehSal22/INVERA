<?php

use App\Models\User;
use App\Models\Video;
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
        Schema::create('kbs_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate(); // To link back to the user who took the assessment
            $table->foreignIdFor(Video::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate(); // To link back to their model scores
            $table->integer('current_branch')->default(1);
            $table->json('answers')->nullable(); // Stores answers for the *current* branch
            $table->json('reports')->nullable(); // Stores completed branch reports
            $table->string('status')->default('in_progress'); // in_progress or completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbs_assessments');
    }
};
