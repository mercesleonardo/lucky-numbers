<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_game_id')->constrained('lottery_games')->onDelete('cascade');
            $table->unsignedInteger('draw_number'); // Número do concurso
            $table->date('draw_date'); // Data do sorteio
            $table->string('location')->nullable(); // Local do sorteio
            $table->json('numbers'); // Números sorteados em formato JSON
            $table->timestamps();

            // Constraint única composta: cada jogo pode ter um draw_number específico
            $table->unique(['lottery_game_id', 'draw_number'], 'contests_lottery_game_draw_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contests');
    }
};
