<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestLotteryEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:test-email {type=success : Tipo de email (success/failure)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o envio de emails de notificação das importações';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type       = $this->argument('type');
        $adminEmail = config('lottery.scheduling.admin_email');

        if (!$adminEmail) {
            $this->error('Email do administrador não configurado!');
            $this->info('Configure LOTTERY_ADMIN_EMAIL no seu .env');

            return Command::FAILURE;
        }

        try {
            if ($type === 'success') {
                $this->sendSuccessEmail($adminEmail);
            } elseif ($type === 'failure') {
                $this->sendFailureEmail($adminEmail);
            } else {
                $this->error("Tipo inválido: {$type}. Use 'success' ou 'failure'");

                return Command::FAILURE;
            }

            $this->info("✅ Email de teste ({$type}) enviado para: {$adminEmail}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erro ao enviar email: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    private function sendSuccessEmail(string $email): void
    {
        $subject = '✅ Lucky Numbers - Importação Concluída com Sucesso';

        $agora             = now();
        $proximaImportacao = $agora->addDay();

        $message = "
🎉 **Importação de Loterias Concluída!**

**Data/Hora:** {$agora->format('d/m/Y H:i:s')}
**Tipo:** Importação de teste
**Status:** ✅ SUCESSO

**Resumo:**
• ✅ Mega-Sena: 1 concurso importado
• ✅ Lotofácil: 1 concurso importado

**Próxima importação:** {$proximaImportacao->format('d/m/Y')} às {$proximaImportacao->format('H:i')}

---
*Lucky Numbers Bot - Sistema Automatizado*
        ";
        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)
                 ->subject($subject)
                 ->from(config('mail.from.address'), 'Lucky Numbers Bot');
        });
    }

    private function sendFailureEmail(string $email): void
    {
        $subject = '❌ Lucky Numbers - Falha na Importação';

        $agora            = now();
        $proximaTentativa = $agora->addHour();

        $message = "
🚨 **ATENÇÃO: Falha na Importação de Loterias**

**Data/Hora:** {$agora->format('d/m/Y H:i:s')}
**Tipo:** Importação de teste
**Status:** ❌ FALHA

**Erro:**
API da Caixa indisponível - Timeout após 30 segundos

**Resumo:**
• ❌ Mega-Sena: Falha na conexão
• ✅ Lotofácil: 1 concurso importado

**Ação Necessária:**
1. Verificar conectividade com a internet
2. Verificar status da API: https://loteriascaixa-api.herokuapp.com/api
3. Tentar importação manual: php artisan lottery:import

**Próxima tentativa:** {$proximaTentativa->format('d/m/Y')} às {$proximaTentativa->format('H:i')}

---
*Lucky Numbers Bot - Sistema Automatizado*
        ";
        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)
                 ->subject($subject)
                 ->from(config('mail.from.address'), 'Lucky Numbers Bot');
        });
    }
}
