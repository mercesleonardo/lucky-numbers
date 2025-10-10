# Componentes Flowbite para Lucky Numbers

## Dashboard Principal (src/components/LotteryDashboard.vue)

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <FwbNavbar fluid>
      <template #logo>
        <FwbNavbarLogo 
          alt="Lucky Numbers Logo" 
          img-src="/logo.svg"
          link="/"
        >
          Lucky Numbers
        </FwbNavbarLogo>
      </template>
      
      <template #default="{ isShowMenu }">
        <FwbNavbarCollapse :isShowMenu="isShowMenu">
          <FwbNavbarLink href="/" :isActive="$route.name === 'home'">
            🏠 Início
          </FwbNavbarLink>
          <FwbNavbarLink href="/generator" :isActive="$route.name === 'generator'">
            🎲 Gerar Jogos
          </FwbNavbarLink>
          <FwbNavbarLink href="/checker" :isActive="$route.name === 'checker'">
            🔍 Verificar Números
          </FwbNavbarLink>
          <FwbNavbarLink href="/results" :isActive="$route.name === 'results'">
            📊 Resultados
          </FwbNavbarLink>
        </FwbNavbarCollapse>
      </template>
      
      <template #menu-icon>
        <FwbNavbarToggle />
      </template>
    </FwbNavbar>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Jogos Gerados Hoje -->
        <FwbCard>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
              🎯
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Jogos Gerados Hoje</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ sessionStats?.generated_today || 0 }}
              </p>
            </div>
          </div>
        </FwbCard>

        <!-- Jogos Restantes -->
        <FwbCard>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
              ⏳
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Jogos Restantes</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ sessionStats?.remaining || 20 }}
              </p>
            </div>
          </div>
        </FwbCard>

        <!-- Último Sorteio -->
        <FwbCard>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
              🏆
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Próximo Sorteio</p>
              <p class="text-lg font-bold text-gray-900">Mega-Sena</p>
              <p class="text-sm text-gray-500">Sábado</p>
            </div>
          </div>
        </FwbCard>
      </div>

      <!-- Últimos Resultados -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Card de Últimos Concursos -->
        <FwbCard>
          <template #header>
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
              📈 Últimos Resultados
            </h3>
          </template>

          <div class="space-y-4" v-if="contests.length">
            <div 
              v-for="contest in contests" 
              :key="contest.game.slug + contest.contest.draw_number"
              class="border-l-4 pl-4 py-2"
              :class="getGameBorderColor(contest.game.slug)"
            >
              <div class="flex justify-between items-start mb-2">
                <div>
                  <h4 class="font-semibold text-gray-900">
                    {{ contest.game.name }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    Concurso {{ contest.contest.draw_number }} - 
                    {{ formatDate(contest.contest.draw_date) }}
                  </p>
                </div>
              </div>
              
              <div class="flex flex-wrap gap-1 mt-2">
                <span 
                  v-for="number in contest.contest.numbers" 
                  :key="number"
                  class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full"
                  :class="getGameColor(contest.game.slug)"
                >
                  {{ number }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <FwbSpinner size="8" />
            <p class="mt-2 text-gray-500">Carregando resultados...</p>
          </div>
        </FwbCard>

        <!-- Card de Ações Rápidas -->
        <FwbCard>
          <template #header>
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
              ⚡ Ações Rápidas
            </h3>
          </template>

          <div class="space-y-4">
            <!-- Geração Rápida -->
            <div class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
              <h4 class="font-semibold text-gray-900 mb-2">🎲 Geração Rápida</h4>
              <p class="text-sm text-gray-600 mb-3">
                Gere 1 jogo para cada modalidade instantaneamente
              </p>
              <FwbButton 
                @click="quickGenerate" 
                :disabled="loading"
                color="blue" 
                class="w-full"
              >
                Gerar Agora
              </FwbButton>
            </div>

            <!-- Verificar Último Jogo -->
            <div class="p-4 border border-gray-200 rounded-lg hover:border-green-300 transition-colors">
              <h4 class="font-semibold text-gray-900 mb-2">🔍 Verificar Números</h4>
              <p class="text-sm text-gray-600 mb-3">
                Confira se seus números já saíram nos últimos sorteios
              </p>
              <FwbButton 
                @click="$router.push('/checker')" 
                color="green" 
                class="w-full"
              >
                Verificar
              </FwbButton>
            </div>

            <!-- Histórico -->
            <div class="p-4 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors">
              <h4 class="font-semibold text-gray-900 mb-2">📊 Estatísticas</h4>
              <p class="text-sm text-gray-600 mb-3">
                Veja números mais sorteados e estatísticas detalhadas
              </p>
              <FwbButton 
                @click="$router.push('/stats')" 
                color="purple" 
                class="w-full"
              >
                Ver Estatísticas
              </FwbButton>
            </div>
          </div>
        </FwbCard>
      </div>
    </div>

    <!-- Toast para notificações -->
    <FwbToast 
      v-if="showToast"
      type="success" 
      @close="showToast = false"
      class="fixed bottom-4 right-4 z-50"
    >
      {{ toastMessage }}
    </FwbToast>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  FwbNavbar,
  FwbNavbarBrand,
  FwbNavbarCollapse,
  FwbNavbarLink,
  FwbNavbarLogo,
  FwbNavbarToggle,
  FwbCard,
  FwbButton,
  FwbSpinner,
  FwbToast
} from 'flowbite-vue'
import { useLottery } from '@/composables/useLottery'

const router = useRouter()
const {
  loading,
  contests,
  sessionStats,
  loadLatestContests,
  generateGames
} = useLottery()

const showToast = ref(false)
const toastMessage = ref('')

onMounted(() => {
  loadLatestContests()
})

const getGameColor = (gameSlug: string) => {
  const colors = {
    'megasena': 'bg-blue-600',
    'lotofacil': 'bg-green-600',
    'quina': 'bg-purple-600'
  }
  return colors[gameSlug] || 'bg-gray-600'
}

const getGameBorderColor = (gameSlug: string) => {
  const colors = {
    'megasena': 'border-blue-500',
    'lotofacil': 'border-green-500',
    'quina': 'border-purple-500'
  }
  return colors[gameSlug] || 'border-gray-500'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('pt-BR')
}

const quickGenerate = async () => {
  try {
    const games = ['megasena', 'lotofacil', 'quina']
    for (const game of games) {
      await generateGames(game, { quantity: 1 })
    }
    
    toastMessage.value = '🎉 Jogos gerados com sucesso!'
    showToast.value = true
    
    setTimeout(() => {
      router.push('/generator')
    }, 1500)
  } catch (error) {
    toastMessage.value = '❌ Erro ao gerar jogos'
    showToast.value = true
  }
}
</script>
```

## Componente de Verificação de Números (src/components/NumberChecker.vue)

```vue
<template>
  <div class="max-w-4xl mx-auto p-6">
    <FwbCard>
      <template #header>
        <h2 class="text-2xl font-bold text-gray-900 text-center">
          🔍 Verificar Seus Números
        </h2>
        <p class="text-gray-600 text-center mt-2">
          Confira quantas vezes seus números acertariam nos últimos sorteios
        </p>
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

        <!-- Input de Números -->
        <div>
          <FwbLabel for="numbers" value="Digite seus números:" class="mb-2" />
          <FwbTextarea
            v-model="numbersInput"
            id="numbers"
            rows="3"
            :placeholder="getPlaceholder()"
            class="w-full"
          />
          <p class="text-sm text-gray-500 mt-1">
            Separe os números por vírgula ou espaço
          </p>
        </div>

        <!-- Botão de Verificação -->
        <div class="text-center">
          <FwbButton
            @click="checkNumbers"
            :disabled="loading || !canCheck"
            color="green"
            size="lg"
            class="w-full sm:w-auto"
          >
            <FwbSpinner v-if="loading" class="mr-2" size="4" />
            {{ loading ? 'Verificando...' : 'Verificar Números' }}
          </FwbButton>
        </div>
      </div>
    </FwbCard>

    <!-- Resultados -->
    <div v-if="checkResult" class="mt-8">
      <FwbCard>
        <template #header>
          <h3 class="text-xl font-semibold text-gray-900">
            📊 Resultado da Verificação
          </h3>
        </template>

        <!-- Seus números -->
        <div class="mb-6">
          <h4 class="font-semibold text-gray-900 mb-3">Seus números:</h4>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="number in parsedNumbers" 
              :key="number"
              class="lottery-ball"
              :class="getGameColorClass(selectedGame)"
            >
              {{ number.toString().padStart(2, '0') }}
            </span>
          </div>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <div class="text-3xl font-bold text-blue-600">
              {{ checkResult.total_matches }}
            </div>
            <div class="text-sm text-gray-600">Acertos Totais</div>
          </div>
          
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <div class="text-3xl font-bold text-green-600">
              {{ checkResult.contests_checked }}
            </div>
            <div class="text-sm text-gray-600">Concursos Verificados</div>
          </div>
          
          <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <div class="text-3xl font-bold text-yellow-600">
              {{ checkResult.best_match }}
            </div>
            <div class="text-sm text-gray-600">Melhor Resultado</div>
          </div>
        </div>

        <!-- Histórico de Acertos -->
        <div v-if="checkResult.matches?.length">
          <h4 class="font-semibold text-gray-900 mb-3">Histórico de Acertos:</h4>
          <div class="space-y-2">
            <div 
              v-for="match in checkResult.matches" 
              :key="match.contest_number"
              class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
            >
              <div>
                <span class="font-semibold">Concurso {{ match.contest_number }}</span>
                <span class="text-sm text-gray-500 ml-2">
                  {{ formatDate(match.draw_date) }}
                </span>
              </div>
              <FwbBadge 
                :type="getMatchBadgeType(match.hits)"
                size="lg"
              >
                {{ match.hits }} acerto{{ match.hits !== 1 ? 's' : '' }}
              </FwbBadge>
            </div>
          </div>
        </div>
      </FwbCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  FwbCard,
  FwbButton,
  FwbLabel,
  FwbSelect,
  FwbTextarea,
  FwbSpinner,
  FwbBadge
} from 'flowbite-vue'
import { useLottery } from '@/composables/useLottery'

const {
  loading,
  checkUserNumbers
} = useLottery()

const selectedGame = ref('megasena')
const numbersInput = ref('')
const checkResult = ref(null)

const gameOptions = [
  { value: 'megasena', name: 'Mega-Sena (6 números)' },
  { value: 'lotofacil', name: 'Lotofácil (15 números)' },
  { value: 'quina', name: 'Quina (5 números)' }
]

const parsedNumbers = computed(() => {
  return numbersInput.value
    .split(/[,\s]+/)
    .map(n => parseInt(n.trim()))
    .filter(n => !isNaN(n))
    .sort((a, b) => a - b)
})

const canCheck = computed(() => {
  const gameConfig = {
    'megasena': 6,
    'lotofacil': 15,
    'quina': 5
  }
  return parsedNumbers.value.length === gameConfig[selectedGame.value]
})

const getPlaceholder = () => {
  const examples = {
    'megasena': 'Ex: 5, 13, 22, 40, 55, 59',
    'lotofacil': 'Ex: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15',
    'quina': 'Ex: 17, 18, 27, 66, 71'
  }
  return examples[selectedGame.value]
}

const checkNumbers = async () => {
  try {
    checkResult.value = await checkUserNumbers(selectedGame.value, parsedNumbers.value)
  } catch (error) {
    console.error('Erro ao verificar números:', error)
  }
}

const getGameColorClass = (gameSlug: string) => {
  const classes = {
    'megasena': 'bg-blue-600',
    'lotofacil': 'bg-green-600',
    'quina': 'bg-purple-600'
  }
  return classes[gameSlug] || 'bg-gray-600'
}

const getMatchBadgeType = (hits: number) => {
  if (hits >= 4) return 'green'
  if (hits >= 3) return 'yellow'
  return 'gray'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('pt-BR')
}
</script>
```
