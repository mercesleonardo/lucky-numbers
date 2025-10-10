# Configuração Frontend - Laravel 12

## Resumo da Configuração

Este documento descreve a configuração implementada para comunicação entre o backend Laravel 12 e aplicações frontend.

## Middlewares Implementados

### 1. CORS (Cross-Origin Resource Sharing)
- **Arquivo**: `config/cors.php`
- **Funcionalidade**: Permite requisições de origins específicos
- **Origins permitidos**:
  - `http://localhost:3000` (React, Next.js)
  - `http://localhost:5173` (Vite)
  - `http://localhost:8080` (Vue CLI)

### 2. JsonResponseMiddleware
- **Arquivo**: `app/Http/Middleware/JsonResponseMiddleware.php`
- **Funcionalidade**: Garante headers corretos para APIs JSON
- **Headers aplicados**:
  - `Content-Type: application/json; charset=utf-8`
  - `X-API-Version: 1.0`
  - `X-Lottery-System: Lucky Numbers`
  - Cache control inteligente (5min para informações, no-cache para geração)

## Configuração Bootstrap (Laravel 12)

O arquivo `bootstrap/app.php` foi configurado com:
```php
->withMiddleware(function (Middleware $middleware): void {
    // CORS para web e API
    $middleware->web([
        \Illuminate\Http\Middleware\HandleCors::class,
    ]);

    $middleware->api([
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\JsonResponseMiddleware::class,
    ]);
})
```

## Rate Limiting

As APIs possuem rate limiting configurado:
- **Consultas gerais**: 60 req/min
- **Verificação de números**: 30 req/min  
- **Geração de jogos**: 10 req/min
- **Informações do sistema**: 120 req/min

## Endpoints Disponíveis

### Concursos
- `GET /api/contests/latest` - Últimos concursos de todos os jogos
- `GET /api/contests/latest/{gameSlug}` - Último concurso de um jogo específico
- `GET /api/contests/exists/{gameSlug}/{drawNumber}` - Verificar se concurso existe
- `POST /api/contests/check-numbers/{gameSlug}` - Verificar números contra histórico

### Geração de Jogos
- `GET /api/games/info` - Informações sobre jogos disponíveis
- `GET /api/games/session-stats` - Estatísticas da sessão atual
- `POST /api/games/generate/{gameSlug}` - Gerar jogos inteligentes

## Teste de Funcionalidade

Para testar se está funcionando:

```bash
# Teste CORS
curl -H "Origin: http://localhost:3000" -I http://localhost:8000/api/contests/latest

# Teste POST com CORS
curl -X POST -H "Origin: http://localhost:3000" -H "Content-Type: application/json" \
     -d '{"quantity":3}' http://localhost:8000/api/games/generate/megasena
```

## Headers Esperados na Resposta

- `Access-Control-Allow-Origin: http://localhost:3000`
- `Content-Type: application/json; charset=utf-8`
- `X-API-Version: 1.0`
- `X-Lottery-System: Lucky Numbers`
- `X-RateLimit-Limit` e `X-RateLimit-Remaining`

## Notas Importantes

1. **Laravel 12**: Configuração de middleware via `bootstrap/app.php`
2. **CORS Preflight**: Suporte automático para requisições OPTIONS
3. **Rate Limiting**: Baseado no IP do cliente
4. **Session Management**: Limite de 20 jogos por IP por dia (sem necessidade de login)
5. **Headers Personalizados**: API version e sistema identificados nos headers
