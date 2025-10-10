# Integração Vue.js com Lucky Numbers API

## Configuração do Projeto Vue.js

### 1. Instalação do Axios
```bash
npm install axios
```

### 2. Configuração do Axios (src/services/api.js)
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

### 3. Serviços da API (src/services/lotteryService.js)
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

### 4. Composable Vue 3 (src/composables/useLottery.js)
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

### 5. Componente de Exemplo (src/components/LotteryGenerator.vue)
```vue
<template>
  <div class="lottery-generator">
    <h2>Gerador de Jogos da Loteria</h2>
    
    <!-- Seletor de Jogo -->
    <div class="game-selector">
      <label for="game">Escolha o jogo:</label>
      <select v-model="selectedGame" id="game">
        <option value="megasena">Mega-Sena</option>
        <option value="lotofacil">Lotofácil</option>
        <option value="quina">Quina</option>
      </select>
    </div>

    <!-- Configurações -->
    <div class="config">
      <label for="quantity">Quantidade de jogos:</label>
      <input 
        v-model.number="quantity" 
        type="number" 
        id="quantity" 
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