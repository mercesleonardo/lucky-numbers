<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lottery_games', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome de exibição, ex: "Lotofácil"
            $table->string('slug')->unique(); // Identificador único, ex: "lotofacil"
            $table->timestamps();
        });

        DB::table('lottery_games')->insert([
            ['name' => 'Mais Milionária', 'slug' => 'maismilionaria', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mega-Sena', 'slug' => 'megasena', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lotofácil', 'slug' => 'lotofacil', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Quina', 'slug' => 'quina', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lotomania', 'slug' => 'lotomania', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Timemania', 'slug' => 'timemania', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dupla Sena', 'slug' => 'duplasena', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Federal', 'slug' => 'federal', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dia de Sorte', 'slug' => 'diadesorte', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Super Sete', 'slug' => 'supersete', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_games');
    }
};
