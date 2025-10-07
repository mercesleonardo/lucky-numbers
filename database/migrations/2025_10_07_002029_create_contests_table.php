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
            $table->unsignedInteger('draw_number')->unique(); // Número do concurso
            $table->date('draw_date'); // Data do sorteio
            $table->string('location')->nullable(); // Local do sorteio
            $table->json('numbers'); // Números sorteados em formato JSON
            $table->boolean('has_accumulated')->default(false); // Indica se houve acumulação
            $table->unsignedInteger('next_draw_number')->nullable(); // Número do próximo concurso
            $table->date('next_draw_date')->nullable(); // Data do próximo concurso
            $table->decimal('estimated_prize_next_draw', 12, 2)->nullable(); // Valor estimado do prêmio do próximo concurso
            $table->json('extra_data')->nullable(); // Dados extras específicos do jogo
            $table->timestamps();
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
