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
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('tier'); // A "faixa" da premiação
            $table->text('description'); // A "descrição", ex: "15 acertos"
            $table->unsignedInteger('winners'); // Número de "ganhadores"
            $table->decimal('prize_amount', 10, 2); // O "valorPremio"
            $table->timestamps();

            $table->unique(['contest_id', 'tier']); // tier único por concurso
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
