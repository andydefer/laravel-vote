<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->string('voter_type');
            $table->unsignedBigInteger('voter_id');
            $table->string('votable_type');
            $table->unsignedBigInteger('votable_id');
            $table->string('type', 20); // positive, negative, abstention, neutral
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['voter_type', 'voter_id', 'votable_type', 'votable_id'], 'unique_vote');
            $table->index(['voter_type', 'voter_id']);
            $table->index(['votable_type', 'votable_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
