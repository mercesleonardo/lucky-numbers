# Lucky Numbers

Sistema para gerenciar dados dos jogos de loteria da Caixa Econômica Federal.

## Sobre o Projeto

Este projeto Laravel permite importar e gerenciar dados dos principais jogos de loteria do Brasil através da API não oficial das Loterias da Caixa. O sistema armazena informações sobre jogos, concursos e premiações.

### Jogos Suportados

- Mega-Sena
- Lotofácil  
- Quina
- Lotomania
- Timemania
- Dupla Sena
- Federal
- Dia de Sorte
- Super Sete
- +Milionária

## Funcionalidades

- **Importação de Dados**: Comando para importar dados atualizados dos jogos de loteria
- **Armazenamento**: Persistência de dados dos jogos, concursos e premiações no banco de dados
- **API Integration**: Integração com a API das Loterias da Caixa
- **Testes Automatizados**: Cobertura de testes para validação das funcionalidades

## Comandos Disponíveis

### Importar Jogos de Loteria (Últimos Resultados)

O sistema oferece um comando Artisan para importar os **últimos resultados** dos jogos de loteria:

```bash
# Importar um jogo específico
php artisan lottery:import megasena

# Importar todos os jogos disponíveis
php artisan lottery:import --all

# Comando interativo (pergunta qual jogo importar)
php artisan lottery:import
```

#### Exemplos de Uso

```bash
# Importar apenas a Mega-Sena
php artisan lottery:import megasena

# Importar Lotofácil
php artisan lottery:import lotofacil

# Importar todos os jogos de uma vez
php artisan lottery:import --all
```

### Importar Dados Históricos (TODOS os Concursos)

Para importar **TODOS os concursos históricos** dos jogos, use o comando dedicado:

```bash
# Importar histórico completo de um jogo específico
php artisan lottery:import-historical megasena --force

# Importar histórico de todos os jogos disponíveis
php artisan lottery:import-historical --all --force

# Importar um range específico de concursos
php artisan lottery:import-historical megasena --from=1 --to=100 --force

# Comando interativo (pergunta qual jogo importar)
php artisan lottery:import-historical
```

#### Opções do Comando Histórico

- `--all`: Importa histórico de todos os jogos disponíveis
- `--from=N`: Define o concurso inicial (padrão: 1)
- `--to=N`: Define o concurso final (padrão: último disponível)
- `--force`: Pula a confirmação de segurança

#### Exemplos de Importação Histórica

```bash
# Importar TODOS os concursos da Mega-Sena (desde 1996)
php artisan lottery:import-historical megasena --force

# Importar apenas os primeiros 100 concursos da Quina
php artisan lottery:import-historical quina --from=1 --to=100 --force

# Importar concursos recentes (últimos 50)
php artisan lottery:import-historical lotofacil --from=2900 --to=2950 --force

# Importar histórico completo de TODOS os jogos
php artisan lottery:import-historical --all --force
```

#### ⚠️ Considerações Importantes sobre Importação Histórica

- **Volume de Dados**: A Mega-Sena tem mais de 2.900 concursos; outros jogos podem ter centenas ou milhares
- **Tempo de Execução**: A importação completa pode levar horas dependendo do jogo
- **Rate Limiting**: O sistema faz uma pausa de 0.1s entre requisições para não sobrecarregar a API
- **Progressão**: Barras de progresso mostram o andamento da importação
- **Recuperação**: Concursos já existentes são pulados automaticamente

#### Estatísticas por Jogo (Aproximadas)

| Jogo | Primeiro Concurso | Concursos (aprox.) | Tempo Estimado |
|------|------------------|-------------------|----------------|
| Mega-Sena | 1996 | ~2.900 | 5-8 horas |
| Lotofácil | 2003 | ~3.000 | 5-8 horas |
| Quina | 1994 | ~6.800 | 12-15 horas |
| Lotomania | 1999 | ~2.500 | 4-6 horas |
| Timemania | 2008 | ~2.100 | 3-5 horas |
| Dupla Sena | 2001 | ~2.600 | 4-6 horas |
| Dia de Sorte | 2018 | ~1.100 | 2-3 horas |
| Super Sete | 2020 | ~750 | 1-2 horas |
| +Milionária | 2022 | ~200 | 30-60 min |
| Federal | 1962 | ~6.000 | 10-12 horas |

### Estrutura de Dados

O sistema armazena os dados em três principais entidades:

#### LotteryGame (Jogos)
- `name`: Nome do jogo (ex: "Mega-Sena")
- `slug`: Identificador único (ex: "megasena")

#### Contest (Concursos)
- `lottery_game_id`: Referência ao jogo
- `draw_number`: Número do concurso
- `draw_date`: Data do sorteio
- `location`: Local do sorteio
- `numbers`: Números sorteados
- `has_accumulated`: Se o prêmio acumulou
- `next_draw_number`: Próximo concurso
- `next_draw_date`: Data do próximo sorteio
- `estimated_prize_next_draw`: Estimativa do próximo prêmio
- `extra_data`: Dados adicionais (JSON)

#### Prize (Prêmios)
- `contest_id`: Referência ao concurso
- `description`: Descrição da faixa (ex: "6 acertos")
- `tier`: Número da faixa
- `winners`: Quantidade de ganhadores
- `prize_amount`: Valor do prêmio

## Instalação e Configuração

1. Clone o repositório
2. Instale as dependências: `composer install`
3. Configure o arquivo `.env`
4. Execute as migrations: `php artisan migrate`
5. Importe os dados: `php artisan lottery:import --all`

## Testes

Execute os testes com:

```bash
php artisan test
```

## Tecnologias Utilizadas

- **Laravel 12**: Framework PHP
- **Pest**: Framework de testes
- **MySQL**: Banco de dados
- **HTTP Client**: Para consumir a API das loterias
