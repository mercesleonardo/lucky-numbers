# 📅 Configuração de Importações Periódicas

Este guia explica como configurar importações automáticas de dados de loteria.

## 🛠️ Configuração do Scheduler do Laravel

### 1. **Configurar Cron Job no Servidor**

O Laravel Scheduler requer apenas **uma entrada no cron** do servidor:

```bash
# Editar crontab
crontab -e

# Adicionar esta linha (substitua o caminho pelo seu projeto)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. **Verificar se o Scheduler está funcionando**

```bash
# Listar todas as tarefas agendadas
php artisan schedule:list

# Testar execução manual
php artisan schedule:run

# Ver logs do scheduler (se configurado)
php artisan schedule:work
```

## ⚙️ Configurações Disponíveis

Todas as configurações podem ser personalizadas via arquivo `.env`:

### **Horários de Importação**
```env
LOTTERY_DAILY_IMPORT_TIME=08:00          # Importação diária dos últimos resultados
LOTTERY_WEEKLY_GAP_FILL_TIME=02:00       # Preenchimento semanal de lacunas
LOTTERY_MIDDAY_POPULAR_TIME=12:00        # Importação meio-dia jogos populares
```

### **Configurações de Gap Fill**
```env
LOTTERY_GAP_FILL_DAYS=7                  # Dias para trás no gap fill
LOTTERY_GAP_FILL_MAX_CONTESTS=10         # Máximo de concursos por gap fill
```

### **Notificações**
```env
LOTTERY_EMAIL_ON_FAILURE=true            # Enviar email em caso de falha
LOTTERY_ADMIN_EMAIL=admin@exemplo.com    # Email do administrador
```

### **Performance da API**
```env
LOTTERY_API_TIMEOUT=30                   # Timeout das requisições (segundos)
LOTTERY_API_RETRY_ATTEMPTS=3             # Tentativas de retry
LOTTERY_REQUEST_DELAY=100000             # Delay entre requisições (microsegundos)
```

## 📊 Agendamentos Configurados

### **1. Importação Diária (08:00)**
- **Comando**: `lottery:scheduled-import --type=latest`
- **Frequência**: Diariamente
- **Função**: Importa os últimos resultados de todos os jogos

### **2. Preenchimento de Lacunas (Domingo 02:00)**
- **Comando**: `lottery:scheduled-import --type=gap-fill --days=7`
- **Frequência**: Semanalmente (domingos)
- **Função**: Preenche lacunas dos últimos 7 dias

### **3. Jogos Populares (12:00)**
- **Comando**: `lottery:scheduled-import --type=latest --games=megasena --games=lotofacil --games=quina`
- **Frequência**: Diariamente
- **Função**: Importação focada nos jogos mais populares

## 🚀 Comandos Manuais

### **Importação Básica**
```bash
# Últimos resultados de todos os jogos
php artisan lottery:scheduled-import --type=latest

# Últimos resultados de jogos específicos
php artisan lottery:scheduled-import --type=latest --games=megasena --games=lotofacil

# Preenchimento de lacunas (últimos 7 dias)
php artisan lottery:scheduled-import --type=gap-fill --days=7

# Preenchimento para jogos específicos
php artisan lottery:scheduled-import --type=gap-fill --games=megasena --days=3
```

### **Comandos Avançados**
```bash
# Importação histórica completa (manual)
php artisan lottery:import-historical megasena --from=1 --to=100 --force

# Importação dos últimos resultados (comando original)
php artisan lottery:import

# Listar jogos disponíveis
php artisan tinker
>>> app(\App\Services\LotteryGameService::class)->getAvailableGames()
```

## 📈 Monitoramento

### **Logs**
```bash
# Ver logs da aplicação
tail -f storage/logs/laravel.log

# Ver logs específicos de importação
grep "importação agendada" storage/logs/laravel.log
```

### **Status das Importações**
```bash
# Verificar últimas importações no banco
php artisan tinker
>>> \App\Models\Contest::latest()->limit(10)->get(['lottery_game_id', 'draw_number', 'draw_date'])
```

## 🔧 Troubleshooting

### **Problema: Cron não executa**
```bash
# Verificar se o cron está rodando
sudo service cron status

# Ver logs do cron
sudo tail -f /var/log/cron

# Testar comando manualmente
cd /path/to/project && php artisan schedule:run
```

### **Problema: Falhas na API**
- Verificar conectividade: `curl https://loteriascaixa-api.herokuapp.com/api/megasena/latest`
- Aumentar timeout: `LOTTERY_API_TIMEOUT=60`
- Aumentar tentativas: `LOTTERY_API_RETRY_ATTEMPTS=5`

### **Problema: Memória/Performance**
- Reduzir delay: `LOTTERY_REQUEST_DELAY=50000`
- Ativar background: `LOTTERY_BACKGROUND_PROCESSING=true`
- Limitar concursos: `LOTTERY_GAP_FILL_MAX_CONTESTS=5`

## 🎯 Boas Práticas

1. **Monitorar Regularmente**: Configure alertas por email para falhas
2. **Backup**: Faça backup do banco antes de grandes importações
3. **Rate Limiting**: Respeite os limites da API com delays adequados
4. **Logs**: Mantenha logs organizados para debug
5. **Testes**: Teste importações em ambiente de desenvolvimento primeiro

## 📱 Notificações

Para receber notificações de falhas por email, configure:

```env
MAIL_MAILER=smtp
MAIL_HOST=seu-smtp.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@exemplo.com
MAIL_PASSWORD=sua-senha
MAIL_FROM_ADDRESS=noreply@exemplo.com
LOTTERY_ADMIN_EMAIL=admin@exemplo.com
```
