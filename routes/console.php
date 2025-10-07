<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{Artisan, Schedule};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamento de importações periódicas

// Importa os últimos resultados diariamente
Schedule::command('lottery:scheduled-import --type=latest')
    ->dailyAt(config('lottery.scheduling.daily_import_time'))
    ->name('daily-lottery-import')
    ->description('Importa os últimos resultados de todas as loterias')
    ->when(fn () => config('lottery.scheduling.email_on_failure'))
    ->emailOutputOnFailure(config('lottery.scheduling.admin_email'))
    ->runInBackground();

// Preenche lacunas semanalmente
Schedule::command('lottery:scheduled-import --type=gap-fill --days=' . config('lottery.scheduling.gap_fill_days'))
    ->weekly()
    ->sundays()
    ->at(config('lottery.scheduling.weekly_gap_fill_time'))
    ->name('weekly-gap-fill')
    ->description('Preenche lacunas dos últimos dias')
    ->runInBackground();

// Importação específica para jogos populares
$popularGames = collect(config('lottery.scheduling.popular_games'))
    ->map(fn ($game) => "--games={$game}")
    ->implode(' ');

Schedule::command("lottery:scheduled-import --type=latest {$popularGames}")
    ->dailyAt(config('lottery.scheduling.midday_popular_time'))
    ->name('popular-games-midday')
    ->description('Importa jogos populares ao meio-dia')
    ->runInBackground();
