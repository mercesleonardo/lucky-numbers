# Lucky Numbers Vue.js Frontend

Este é um exemplo de como criar um frontend Vue.js para integrar com a API Lucky Numbers.

## Instalação

```bash
# Criar projeto Vue.js
npm create vue@latest lucky-numbers-frontend

# Entrar no diretório
cd lucky-numbers-frontend

# Instalar dependências
npm install

# Instalar Axios para requisições HTTP
npm install axios
```

## Estrutura do Projeto

```
src/
├── components/
│   ├── LotteryGenerator.vue
│   ├── ContestChecker.vue
│   └── SessionStats.vue
├── composables/
│   └── useLottery.js
├── services/
│   ├── api.js
│   └── lotteryService.js
├── views/
│   ├── Home.vue
│   └── Games.vue
├── App.vue
└── main.js
```

## Comandos

```bash
# Desenvolvimento
npm run dev

# Build para produção
npm run build

# Preview da build
npm run preview

# Lint
npm run lint
```

## URLs de Desenvolvimento

O projeto Vue.js rodará em:
- `http://localhost:5173` (Vite padrão)

Esta URL já está configurada no CORS do backend Laravel.

## Exemplo de Uso Básico

```javascript
import { useLottery } from '@/composables/useLottery'

const { generateGames, loading, error } = useLottery()

// Gerar jogos da Mega-Sena
const gerarJogos = async () => {
  try {
    const result = await generateGames('megasena', {
      quantity: 3,
      excludeNumbers: [1, 7, 13]
    })
    console.log('Jogos gerados:', result.games)
  } catch (err) {
    console.error('Erro:', err)
  }
}
```

## Funcionalidades Disponíveis

1. **Geração de Jogos Inteligentes**
   - Mega-Sena, Lotofácil e Quina
   - Evita números que saíram recentemente
   - Limite de 20 jogos por sessão

2. **Verificação de Números**
   - Confira seus números contra histórico
   - Veja quantas vezes acertaria nos últimos concursos

3. **Informações dos Concursos**
   - Últimos resultados
   - Verificação de existência de concursos

4. **Rate Limiting Inteligente**
   - Limites diferenciados por tipo de operação
   - Headers informativos sobre limites

## Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto:

```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME=Lucky Numbers
```

## Integração com Pinia (Store)

```javascript
// stores/lottery.js
import { defineStore } from 'pinia'
import { lotteryService } from '@/services/lotteryService'

export const useLotteryStore = defineStore('lottery', {
  state: () => ({
    contests: [],
    generatedGames: {},
    sessionStats: null,
    loading: false,
    error: null
  }),
  
  actions: {
    async fetchLatestContests() {
      this.loading = true
      try {
        this.contests = await lotteryService.getLatestContests()
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },
    
    async generateGames(gameSlug, options) {
      this.loading = true
      try {
        const result = await lotteryService.generateGames(gameSlug, options)
        this.generatedGames[gameSlug] = result.games
        this.sessionStats = result.session_stats
        return result
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
```
