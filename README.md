# Lucky Numbers - Sistema Inteligente de Loterias

Sistema Laravel para gerenciar dados dos jogos de loteria e gerar jogos inteligentes baseados em análise histórica.

## 🎯 Sobre o Projeto

Este projeto oferece uma **plataforma completa** para:
- **Importação automática** de dados das loterias brasileiras
- **Geração inteligente** de jogos baseada em algoritmos
- **APIs públicas** para consulta e geração de jogos
- **Sistema de sessão** sem necessidade de login

### 🎲 Jogos Suportados (Otimizados)

- **Mega-Sena** (6 números de 1-60)
- **Lotofácil** (15 números de 1-25)
- **Quina** (5 números de 1-80)

> **Nota**: O sistema foi otimizado para focar nos 3 jogos mais populares, oferecendo melhor performance e geração inteligente.

## 🚀 Funcionalidades Principais

### 📊 **Importação e Armazenamento**
- **Importação otimizada** com processamento paralelo automático
- **Cache inteligente** para melhor performance
- **Agendamento automático** para atualizações diárias
- **Validação e correção** automática de dados

### 🧠 **Geração Inteligente de Jogos**
- **Algoritmos smart** que evitam números premiados recentemente
- **Limite de 20 jogos** por usuário/IP por dia
- **Validação automática** por tipo de jogo
- **Controle de sessão** sem necessidade de login

### 🌐 **APIs Públicas RESTful**
- **Rate limiting** inteligente por tipo de operação
- **Throttling personalizado** para proteção contra abuso
- **Documentação completa** para integração frontend

## 💻 Comandos Disponíveis

### 🔄 **Importação Simplificada e Otimizada**

```bash
# Importar jogo específico (com processamento inteligente)
php artisan lottery:import megasena

# Importar todos os jogos suportados
php artisan lottery:import --all --force

# Sistema detecta automaticamente:
# - Processamento paralelo para >10 concursos
# - Cache para validações rápidas
# - Batch inserts para melhor performance
```

### 📈 **Exemplos de Performance**

```bash
# Mega-Sena: ~2.900 concursos
# Antes: ~15 horas | Após otimização: ~4 horas ⚡

# Lotofácil: ~3.500 concursos  
# Antes: ~18 horas | Após otimização: ~4.5 horas ⚡

# Quina: ~6.800 concursos
# Antes: ~35 horas | Após otimização: ~8.5 horas ⚡
```

## 🌐 APIs Públicas

### 📊 **Consulta de Concursos**

```bash
# Últimos concursos de todos os jogos
GET /api/contests/latest

# Último concurso de jogo específico  
GET /api/contests/latest/{jogo}

# Verificar se números já ganharam
POST /api/contests/check-numbers/{jogo}
Body: {"numbers": [1,2,3,4,5,6]}
```

### 🎲 **Geração Inteligente de Jogos**

```bash
# Informações dos jogos suportados
GET /api/games/info

# Gerar jogos inteligentes (1-20 por sessão)
POST /api/games/generate/{jogo}
Body: {"count": 5}

# Verificar estatísticas da sessão
GET /api/games/session-stats
```

### 🛡️ **Rate Limiting Implementado**

| Endpoint | Limite | Justificativa |
|----------|--------|---------------|
| `/games/info` | 120/min | Informações estáticas |
| `/contests/latest` | 60/min | Consultas simples |
| `/contests/check-numbers` | 30/min | Busca no histórico |
| `/games/generate` | 10/min | Operação computacionalmente intensiva |

## ⚙️ Agendamento Automático

O sistema possui **4 importações automáticas** configuradas:

```bash
# 08:00 - Importação diária dos últimos resultados
# 12:00 - Jogos populares (megasena, lotofacil, quina)  
# 22:00 - Importação geral completa (NOVO!)
# 02:00 (domingos) - Preenchimento de lacunas

# Para ativar o scheduler:
crontab -e
# Adicionar: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## 🧠 Sistema de Geração Inteligente

### **Algoritmos Implementados**
- **Análise histórica**: Verifica últimos 10 concursos
- **Evita sobreposição**: Máximo 40% de números premiados recentes
- **Validação automática**: Por tipo de jogo e regras específicas
- **Controle de sessão**: 20 jogos por IP/dia sem necessidade de login

### **Como Usar no Frontend**

```javascript
// 1. Consultar último concurso
const latest = await fetch('/api/contests/latest/megasena');

// 2. Gerar 5 jogos inteligentes
const games = await fetch('/api/games/generate/megasena', {
    method: 'POST',
    body: JSON.stringify({count: 5})
});

// 3. Verificar se números já ganharam
const check = await fetch('/api/contests/check-numbers/megasena', {
    method: 'POST', 
    body: JSON.stringify({numbers: [1,2,3,4,5,6]})
});
```

## 🏗️ Estrutura de Dados Otimizada

#### Contest (Concursos) - Otimizado
- `lottery_game_id`: Referência ao jogo
- `draw_number`: Número do concurso
- `draw_date`: Data do sorteio (cast automático)
- `location`: Local do sorteio
- `numbers`: Números sorteados (cast para array)

#### Prize (Prêmios) - Simplificado
- `contest_id`: Referência ao concurso
- `tier`: Faixa de premiação
- `description`: Descrição da faixa
- `winners`: Quantidade de ganhadores
- `prize_amount`: Valor do prêmio

> **Otimização**: Removidos campos desnecessários para melhor performance

## 🚀 Instalação e Configuração

```bash
# 1. Clone e instale dependências
git clone [repositório]
composer install

# 2. Configure ambiente
cp .env.example .env
php artisan key:generate

# 3. Configure banco de dados no .env
DB_CONNECTION=mysql
DB_DATABASE=lucky_numbers

# 4. Execute migrations
php artisan migrate

# 5. Importe dados iniciais
php artisan lottery:import --all --force

# 6. Configure scheduler (opcional)
crontab -e
# Adicionar: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Testes específicos
php artisan test --filter=LotteryGameServiceTest
php artisan test tests/Feature/
```

## 🛠️ Tecnologias e Otimizações

- **Laravel 12**: Framework PHP moderno
- **Pest 4**: Framework de testes com browser testing
- **MySQL**: Banco de dados otimizado
- **Jobs/Queue**: Processamento paralelo automático
- **Cache**: Sistema inteligente de cache
- **Throttling**: Rate limiting por operação
- **Batch Operations**: Inserções em lote para performance
