# Integração Vue.js com Lucky Numbers API

## Configuração do Projeto Vue.js

### 1. Instalação das Dependências
```bash
# Instalar Axios para requisições HTTP
npm install axios

# Instalar Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Instalar Flowbite para Vue
npm install flowbite
npm install flowbite-vue
```

### 2. Configuração do Tailwind CSS (tailwind.config.js)
```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
    "./node_modules/flowbite/**/*.js",
    "./node_modules/flowbite-vue/**/*.{js,jsx,ts,tsx,vue}"
  ],
  theme: {
    extend: {
      colors: {
        // Cores personalizadas para loteria
        lottery: {
          primary: '#1e40af',    // Azul principal
          secondary: '#059669',  // Verde para acertos
          accent: '#dc2626',     // Vermelho para alertas
          gold: '#f59e0b',       // Dourado para prêmios
        }
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}
```

### 3. Configuração do CSS Principal (src/style.css)
```css
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* Estilos personalizados para números da loteria */
.lottery-number {
  @apply w-12 h-12 rounded-full bg-lottery-primary text-white font-bold text-lg flex items-center justify-center shadow-lg;
}

.lottery-number-selected {
  @apply bg-lottery-gold transform scale-105 shadow-xl;
}

.lottery-ball {
  @apply lottery-number transition-all duration-200 hover:scale-110 cursor-pointer;
}

/* Animações para sorteio */
@keyframes bounce-number {
  0%, 20%, 53%, 80%, 100% {
    transform: translate3d(0,0,0);
  }
  40%, 43% {
    transform: translate3d(0, -8px, 0);
  }
  70% {
    transform: translate3d(0, -4px, 0);
  }
  90% {
    transform: translate3d(0, -2px, 0);
  }
}

.animate-bounce-number {
  animation: bounce-number 1s ease-in-out;
}
```

### 4. Configuração do Main.js com Flowbite
```javascript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'

import App from './App.vue'

// Importar CSS do Tailwind
import './style.css'

// Importar Flowbite
import 'flowbite'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
```

### 5. Configuração do Axios (src/services/api.js)
```javascript
import axios from 'axios'

// Configuração base da API
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Interceptor para respostas
api.interceptors.response.use(
  response => response,
  error => {
    console.error('API Error:', error.response?.data || error.message)
    return Promise.reject(error)
  }
)

export default api
```

### 6. Serviços da API (src/services/lotteryService.js)
```javascript
import api from './api'

export const lotteryService = {
  // Obter últimos concursos
  async getLatestContests() {
    const response = await api.get('/contests/latest')
    return response.data
  },

  // Obter último concurso de um jogo específico
  async getLatestContest(gameSlug) {
    const response = await api.get(`/contests/latest/${gameSlug}`)
    return response.data
  },

  // Verificar se concurso existe
  async contestExists(gameSlug, drawNumber) {
    const response = await api.get(`/contests/exists/${gameSlug}/${drawNumber}`)
    return response.data
  },

  // Verificar números do usuário
  async checkNumbers(gameSlug, numbers) {
    const response = await api.post(`/contests/check-numbers/${gameSlug}`, {
      numbers
    })
    return response.data
  },

  // Obter informações dos jogos
  async getGamesInfo() {
    const response = await api.get('/games/info')
    return response.data
  },

  // Obter estatísticas da sessão
  async getSessionStats() {
    const response = await api.get('/games/session-stats')
    return response.data
  },

  // Gerar jogos inteligentes
  async generateGames(gameSlug, options = {}) {
    const { quantity = 1, excludeNumbers = [] } = options
    const response = await api.post(`/games/generate/${gameSlug}`, {
      quantity,
      excludeNumbers
    })
    return response.data
  }
}
```

### 7. Composable Vue 3 (src/composables/useLottery.js)
```javascript
import { ref, reactive } from 'vue'
import { lotteryService } from '@/services/lotteryService'

export function useLottery() {
  const loading = ref(false)
  const error = ref(null)
  const contests = ref([])
  const sessionStats = ref(null)
  const gamesInfo = ref(null)

  // Estado reativo para jogos gerados
  const generatedGames = reactive({
    megasena: [],
    lotofacil: [],
    quina: []
  })

  // Carregar últimos concursos
  const loadLatestContests = async () => {
    loading.value = true
    error.value = null
    try {
      contests.value = await lotteryService.getLatestContests()
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  // Gerar jogos
  const generateGames = async (gameSlug, options) => {
    loading.value = true
    error.value = null
    try {
      const result = await lotteryService.generateGames(gameSlug, options)
      generatedGames[gameSlug] = result.games
      sessionStats.value = result.session_stats
      return result
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Verificar números
  const checkUserNumbers = async (gameSlug, numbers) => {
    loading.value = true
    error.value = null
    try {
      return await lotteryService.checkNumbers(gameSlug, numbers)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Carregar informações dos jogos
  const loadGamesInfo = async () => {
    try {
      gamesInfo.value = await lotteryService.getGamesInfo()
    } catch (err) {
      error.value = err.message
    }
  }

  return {
    loading,
    error,
    contests,
    sessionStats,
    gamesInfo,
    generatedGames,
    loadLatestContests,
    generateGames,
    checkUserNumbers,
    loadGamesInfo
  }
}
```

### 8. Componente com Flowbite Vue (src/components/LotteryGenerator.vue)
```vue
<template>
  <div class="max-w-4xl mx-auto p-6">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">🎰 Gerador de Jogos da Loteria</h1>
      <p class="text-gray-600">Gere jogos inteligentes para Mega-Sena, Lotofácil e Quina</p>
    </div>

    <!-- Card Principal -->
    <FwbCard class="mb-6">
      <template #header>
        <h2 class="text-xl font-semibold text-gray-900">Configuração do Jogo</h2>
      </template>

      <div class="space-y-6">
        <!-- Seletor de Jogo -->
        <div>
          <FwbLabel for="game" value="Escolha o jogo:" class="mb-2" />
          <FwbSelect 
            v-model="selectedGame" 
            id="game"
            :options="gameOptions"
            class="w-full"
          />
        </div>

        <!-- Quantidade de Jogos -->
        <div>
          <FwbLabel for="quantity" value="Quantidade de jogos:" class="mb-2" />
          <FwbInput
            v-model.number="quantity"
            type="number"
            id="quantity"
            min="1"
            max="10"
            placeholder="Digite a quantidade"
            class="w-full"
          />
          <p class="text-sm text-gray-500 mt-1">Máximo 10 jogos por vez</p>
        </div>

        <!-- Números para Excluir -->
        <div>
          <FwbLabel for="exclude" value="Números para excluir (opcional):" class="mb-2" />
          <FwbInput
            v-model="excludeInput"
            type="text"
            id="exclude"
            placeholder="Ex: 1, 7, 13, 25"
            class="w-full"
          />
          <p class="text-sm text-gray-500 mt-1">Separe os números por vírgula</p>
        </div>

        <!-- Botão de Geração -->
        <div class="text-center">
          <FwbButton
            @click="handleGenerate"
            :disabled="loading || !canGenerate"
            color="blue"
            size="lg"
            class="w-full sm:w-auto"
          >
            <FwbSpinner v-if="loading" class="mr-2" size="4" />
            {{ loading ? 'Gerando...' : 'Gerar Jogos' }}
          </FwbButton>
        </div>
      </div>
    </FwbCard>

    <!-- Estatísticas da Sessão -->
    <FwbAlert v-if="sessionStats" type="info" class="mb-6">
      <template #icon>
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
        </svg>
      </template>
      <div>
        <span class="font-semibold">Sessão Atual:</span>
        {{ sessionStats.generated_today }} jogos gerados hoje | 
        {{ sessionStats.remaining }} restantes
      </div>
    </FwbAlert>

    <!-- Erro -->
    <FwbAlert v-if="error" type="danger" @dismiss="error = null" class="mb-6">
      <template #icon>
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
      </template>
      {{ error }}
    </FwbAlert>

    <!-- Jogos Gerados -->
    <div v-if="generatedGames[selectedGame]?.length" class="space-y-4">
      <h3 class="text-2xl font-bold text-gray-900 text-center mb-6">
        🎯 Jogos Gerados - {{ getGameName(selectedGame) }}
      </h3>
      
      <div class="grid gap-4">
        <FwbCard 
          v-for="(game, index) in generatedGames[selectedGame]" 
          :key="index"
          class="game-card"
        >
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900">Jogo {{ index + 1 }}</h4>
            <FwbButton color="green" size="sm" @click="copyGame(game)">
              📋 Copiar
            </FwbButton>
          </div>
          
          <div class="flex flex-wrap gap-2 justify-center">
            <span 
              v-for="number in game" 
              :key="number"
              class="lottery-ball animate-bounce-number"
              :style="{ animationDelay: `${game.indexOf(number) * 0.1}s` }"
            >
              {{ number.toString().padStart(2, '0') }}
            </span>
          </div>
        </FwbCard>
      </div>

      <!-- Botão para Gerar Mais -->
      <div class="text-center mt-6">
        <FwbButton
          @click="handleGenerate"
          :disabled="loading || sessionStats?.remaining <= 0"
          color="purple"
          outline
        >
          🎲 Gerar Mais Jogos
        </FwbButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  FwbCard, 
  FwbButton, 
  FwbInput, 
  FwbLabel, 
  FwbSelect, 
  FwbAlert, 
  FwbSpinner 
} from 'flowbite-vue'
import { useLottery } from '@/composables/useLottery'

const {
  loading,
  error,
  sessionStats,
  generatedGames,
  generateGames
} = useLottery()

const selectedGame = ref('megasena')
const quantity = ref(1)
const excludeInput = ref('')

const gameOptions = [
  { value: 'megasena', name: 'Mega-Sena (6 números)' },
  { value: 'lotofacil', name: 'Lotofácil (15 números)' },
  { value: 'quina', name: 'Quina (5 números)' }
]

const excludeNumbers = computed(() => {
  if (!excludeInput.value) return []
  return excludeInput.value
    .split(',')
    .map(n => parseInt(n.trim()))
    .filter(n => !isNaN(n))
})

const canGenerate = computed(() => {
  return quantity.value >= 1 && quantity.value <= 10
})

const getGameName = (slug: string) => {
  const game = gameOptions.find(g => g.value === slug)
  return game?.name || slug
}

const handleGenerate = async () => {
  try {
    await generateGames(selectedGame.value, {
      quantity: quantity.value,
      excludeNumbers: excludeNumbers.value
    })
  } catch (err) {
    console.error('Erro ao gerar jogos:', err)
  }
}

const copyGame = async (game: number[]) => {
  const gameText = game.map(n => n.toString().padStart(2, '0')).join(' - ')
  try {
    await navigator.clipboard.writeText(gameText)
    // Adicionar toast de sucesso aqui
    console.log('Jogo copiado:', gameText)
  } catch (err) {
    console.error('Erro ao copiar:', err)
  }
}
</script>

<style scoped>
.game-card {
  @apply transition-transform duration-200 hover:scale-[1.02] hover:shadow-lg;
}

.lottery-ball {
  @apply w-12 h-12 rounded-full bg-lottery-primary text-white font-bold text-lg flex items-center justify-center shadow-lg transition-all duration-200 hover:scale-110;
}

/* Cores específicas por jogo */
.game-megasena .lottery-ball {
  @apply bg-blue-600;
}

.game-lotofacil .lottery-ball {
  @apply bg-green-600;
}

.game-quina .lottery-ball {
  @apply bg-purple-600;
}
</style>
``` 
        min="1" 
        max="10"
      >
    </div>

    <!-- Números para excluir -->
    <div class="exclude-numbers">
      <label for="exclude">Números para excluir (separados por vírgula):</label>
      <input 
        v-model="excludeInput" 
        type="text" 
        id="exclude"
        placeholder="ex: 1,7,13"
      >
    </div>

    <!-- Botão de geração -->
    <button 
      @click="handleGenerate" 
      :disabled="loading || !canGenerate"
      class="generate-btn"
    >
      {{ loading ? 'Gerando...' : 'Gerar Jogos' }}
    </button>

    <!-- Estatísticas da sessão -->
    <div v-if="sessionStats" class="session-stats">
      <p>Jogos gerados hoje: {{ sessionStats.generated_today }}</p>
      <p>Restantes: {{ sessionStats.remaining }}</p>
    </div>

    <!-- Erro -->
    <div v-if="error" class="error">
      {{ error }}
    </div>

    <!-- Jogos gerados -->
    <div v-if="generatedGames[selectedGame]?.length" class="generated-games">
      <h3>Jogos Gerados:</h3>
      <div 
        v-for="(game, index) in generatedGames[selectedGame]" 
        :key="index"
        class="game"
      >
        <span 
          v-for="number in game" 
          :key="number"
          class="number"
        >
          {{ number.toString().padStart(2, '0') }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useLottery } from '@/composables/useLottery'

const {
  loading,
  error,
  sessionStats,
  generatedGames,
  generateGames
} = useLottery()

const selectedGame = ref('megasena')
const quantity = ref(1)
const excludeInput = ref('')

const excludeNumbers = computed(() => {
  if (!excludeInput.value) return []
  return excludeInput.value
    .split(',')
    .map(n => parseInt(n.trim()))
    .filter(n => !isNaN(n))
})

const canGenerate = computed(() => {
  return quantity.value >= 1 && quantity.value <= 10
})

const handleGenerate = async () => {
  try {
    await generateGames(selectedGame.value, {
      quantity: quantity.value,
      excludeNumbers: excludeNumbers.value
    })
  } catch (err) {
    console.error('Erro ao gerar jogos:', err)
  }
}
</script>

<style scoped>
.lottery-generator {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.game-selector, .config, .exclude-numbers {
  margin-bottom: 15px;
}

.generate-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  margin-bottom: 20px;
}

.generate-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.session-stats {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.error {
  background: #f8d7da;
  color: #721c24;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.generated-games {
  margin-top: 20px;
}

.game {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
  padding: 10px;
  background: #e9ecef;
  border-radius: 5px;
}

.number {
  background: #007bff;
  color: white;
  padding: 5px 8px;
  border-radius: 50%;
  font-weight: bold;
  min-width: 30px;
  text-align: center;
}
</style>
```

### 6. Configuração do Vite (vite.config.js)
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    port: 5173,
    host: true
  }
})
```

## URLs de Desenvolvimento Suportadas

O backend já suporta estas URLs do Vue.js:
- `http://localhost:5173` (Vite padrão)
- `http://localhost:8080` (Vue CLI)
- `http://127.0.0.1:5173`
- `http://127.0.0.1:8080`

## Exemplo de Uso no Componente Principal

```vue
<template>
  <div id="app">
    <LotteryGenerator />
  </div>
</template>

<script setup>
import LotteryGenerator from '@/components/LotteryGenerator.vue'
</script>
```

## Rate Limiting

Lembre-se dos limites de requisição:
- **Geração de jogos**: 10 req/min
- **Verificação de números**: 30 req/min
- **Consultas gerais**: 60 req/min
- **Informações**: 120 req/min

## Tratamento de Erros

O backend retorna erros estruturados que podem ser facilmente tratados no Vue.js:

```javascript
// Exemplo de tratamento de erro de rate limiting
try {
  await generateGames('megasena', { quantity: 5 })
} catch (error) {
  if (error.response?.status === 429) {
    alert('Muitas tentativas. Aguarde um momento.')
  } else {
    alert('Erro ao gerar jogos: ' + error.message)
  }
}
```

Agora você tem tudo o que precisa para criar um frontend Vue.js que se comunica perfeitamente com sua API Laravel 12! 🚀
