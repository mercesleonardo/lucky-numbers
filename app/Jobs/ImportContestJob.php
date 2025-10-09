<?php

namespace App\Jobs;

use App\Services\LotteryGameService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportContestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $gameName,
        public int $contestNumber,
        public ?string $jobId = null
    ) {
        $this->jobId = $jobId ?? uniqid('import_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(LotteryGameService $lotteryService): void
    {
        try {
            $result = $lotteryService->importContest($this->gameName, $this->contestNumber);

            if (!$result['success']) {
                Log::warning("Falha na importação do concurso {$this->contestNumber} ({$this->gameName})", [
                    'error'  => $result['error'] ?? 'Erro desconhecido',
                    'job_id' => $this->jobId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro no job de importação", [
                'game'    => $this->gameName,
                'contest' => $this->contestNumber,
                'error'   => $e->getMessage(),
                'job_id'  => $this->jobId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de importação falhou completamente", [
            'game'    => $this->gameName,
            'contest' => $this->contestNumber,
            'error'   => $exception->getMessage(),
            'job_id'  => $this->jobId,
        ]);
    }
}
