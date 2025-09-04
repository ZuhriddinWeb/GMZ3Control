<template>
  <VaModal v-model="openLocal" fullscreen title="Yangi formula yaratish" ok-text="Tasdiqlash" cancel-text="Bekor qilish"
    @ok="emitSave" close-button>
    <div class="flex gap-4">
      <!-- CHAP: KALKULYATOR -->
      <div class="calculator">
        <div class="answer">{{ answer }}</div>
        <div class="display">{{ logList + current }}</div>

        <div @click="clear" id="clear" class="btn operator">C</div>
        <div @click="sign" id="sign" class="btn operator">+/-</div>
        <div @click="percent" id="percent" class="btn operator">%</div>
        <div @click="divide" id="divide" class="btn operator">/</div>

        <div @click="append('7')" id="n7" class="btn">7</div>
        <div @click="append('8')" id="n8" class="btn">8</div>
        <div @click="append('9')" id="n9" class="btn">9</div>
        <div @click="times" id="times" class="btn operator">*</div>

        <div @click="append('4')" id="n4" class="btn">4</div>
        <div @click="append('5')" id="n5" class="btn">5</div>
        <div @click="append('6')" id="n6" class="btn">6</div>
        <div @click="minus" id="minus" class="btn operator">-</div>

        <div @click="append('1')" id="n1" class="btn">1</div>
        <div @click="append('2')" id="n2" class="btn">2</div>
        <div @click="append('3')" id="n3" class="btn">3</div>
        <div @click="plus" id="plus" class="btn operator">+</div>

        <div @click="append('0')" id="n0" class="zero">0</div>
        <div @click="dot" id="dot" class="btn">.</div>
        <div @click="equal" id="equal" class="btn operator">=</div>
        <div @click="append('(')" id="lb" class="btn p-4">(</div>
        <div @click="append(')')" id="rb" class="btn p-4">)</div>
      </div>

      <!-- O‘NG: SEX → SAHIFA → GURUH → PARAMETR -->
      <div class="flex-1 min-w-[420px]">
        <div class="mb-2 text-xs text-gray-500">Sex → Sahifa → Guruh</div>
        <!-- UNIVERSAL VAQT/AGREGAT SOZLAMALARI (sana yo'q) -->
        <div class="rounded border p-2 mb-3">
          <div class="mb-2 text-xs text-gray-500">Agregatsiya va oynaviy qoida</div>

          <div class="flex flex-wrap items-center gap-2 mb-2">
            <span class="text-xs text-gray-500">Davr:</span>
            <VaButton v-for="opt in aggOptions" :key="opt.value" size="small"
              :color="opt.value === agg.agg ? 'primary' : 'secondary'" @click="agg.agg = opt.value">{{ opt.label }}
            </VaButton>

            <span class="ml-3 text-xs text-gray-500">Funktsiya:</span>
            <VaSelect v-model="agg.func" :options="funcOptions" value-by="value" text-by="text" class="min-w-[140px]" />

            <span class="ml-3 text-xs text-gray-500">Qamrov:</span>
            <VaSelect v-model="agg.scope" :options="scopeOptions" value-by="value" text-by="text"
              class="min-w-[140px]" />

            <VaInput v-if="agg.scope !== 'CURRENT'" v-model.number="agg.n" type="number" min="1" class="w-20"
              :label="agg.scope === 'PREV' ? 'nechta orqaga' : 'oyna hajmi'" />
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">Qo'llash:</span>
            <VaButton size="small" :color="applyMode === 'global' ? 'primary' : 'secondary'"
              @click="applyMode = 'global'">Global</VaButton>
            <VaButton size="small" :color="applyMode === 'next' ? 'primary' : 'secondary'" @click="applyMode = 'next'">
              Keyingi parametr</VaButton>

            <span class="text-xs text-gray-500 ml-2">
              {{ aggSummary }}
            </span>
          </div>
        </div>

        <!-- SEXLAR -->
        <div class="mb-3 flex flex-wrap gap-2">
          <VaButton v-for="(sex, i) in items" :key="`sex-${sex.sexId}`" size="small"
            :color="i === sel.sex ? 'primary' : 'secondary'" @click="selectSex(i)">
            {{ sex.sexName || `Sex #${sex.sexId}` }}
          </VaButton>
        </div>

        <!-- SAHIFALAR -->
        <div v-if="pages.length" class="mb-3 flex flex-wrap gap-2">
          <VaButton v-for="(pg, j) in pages" :key="`page-${pg.pageId}`" size="small"
            :color="j === sel.page ? 'primary' : 'secondary'" @click="selectPage(j)">
            {{ pg.pageName || `Sahifa ${pg.pageId}` }}
          </VaButton>
        </div>

        <!-- GROUHLAR -->
        <div v-if="groups.length" class="mb-3 flex flex-wrap gap-2">
          <VaButton v-for="(g, k) in groups" :key="`grp-${g.groupId}`" size="small"
            :color="k === sel.group ? 'primary' : 'secondary'" @click="selectGroup(k)">
            {{ g.groupName || `Guruh #${g.groupId}` }}
          </VaButton>
        </div>

        <!-- PARAMETRLAR -->
        <div class="border rounded p-2 min-h-[160px]">
          <div v-if="parameters.length" class="flex flex-wrap gap-2">
            <VaButton v-for="p in parameters" :key="`param-${p.id}`" color="warning" text-color="black"
              :style="{ borderRadius: '0' }" @click="appendParam(p)">
              {{ p.name || p.id }}
            </VaButton>
          </div>
          <div v-else class="text-gray-500 text-sm">Parametrlar topilmadi</div>
        </div>
      </div>
    </div>

    <VaTextarea class="w-full mt-3" v-model="result.Comment" :max-length="125" label="Izoh" />
  </VaModal>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { VaModal, VaButton, VaTextarea, VaSelect, VaInput, useToast } from 'vuestic-ui'
import anime from 'animejs/lib/anime.es.js'
import { useI18n } from 'vue-i18n';
const { locale, t } = useI18n();
/* Props/Emits */
const props = defineProps({
  parameter: { type: [Number, String], required: true },
  modelValue: { type: Boolean, default: false },
  docId: { type: [Number], required: true },
})

const emit = defineEmits(['update:modelValue', 'save'])

/* Modal v-model */
const openLocal = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

/* Toast */
const { init } = useToast()

/* Backend daraxti */
const items = ref([])
/*
 items: [
   { sexId, sexName, pages: [
       { pageId, pageName, groups: [
           { groupId, groupName, parameters: [ { id, name }, ... ] }
       ]}
   ]}
 ]
*/
/* --- UI holati (sanasiz, universal) --- */

const aggOptions = [
  { value: 'RAW', label: 'Asosiy' },
  { value: 'HOUR', label: 'Soatlik' },
  { value: 'SHIFT', label: 'Smenalik' },
  { value: 'DAY', label: 'Kunlik' },
  { value: 'WEEK', label: 'Haftalik' },
  { value: 'MONTH', label: 'Oylik' },
  { value: 'YEAR', label: 'Yillik' },
]

const funcOptions = [
  { text: 'Qiymat (joriy)', value: 'VALUE' },
  { text: 'O‘rtacha', value: 'AVG' },
  { text: 'Yig‘indi', value: 'SUM' },
  { text: 'Min', value: 'MIN' },
  { text: 'Max', value: 'MAX' },
]

const scopeOptions = [
  { text: 'Joriy davr', value: 'CURRENT' },
  { text: 'Oldingi davr(lar)', value: 'PREV' },
  { text: 'Sirpanma oynaviy', value: 'ROLLING' },
]

const applyMode = ref('global') // 'global' | 'next'
const agg = reactive({
  agg: 'DAY',
  func: 'VALUE',
  scope: 'CURRENT', // CURRENT | PREV | ROLLING
  n: 1,             // PREV/ROLLING uchun
})

const aggSummary = computed(() => {
  const a = aggOptions.find(x => x.value === agg.agg)?.label ?? agg.agg
  const f = funcOptions.find(x => x.value === agg.func)?.text ?? agg.func
  if (agg.scope === 'CURRENT') return `${a}, ${f}, joriy davr`
  if (agg.scope === 'PREV') return `${a}, ${f}, oldingi ${agg.n} davr`
  return `${a}, ${f}, oxirgi ${agg.n} davr (rolling)`
})
function appendParam(p) {
  // Tokenni universal meta-bilan yozamiz (sanasiz)
  const fVal = typeof agg.func  === 'object' ? agg.func.value  : agg.func
  const sVal = typeof agg.scope === 'object' ? agg.scope.value : agg.scope
  const meta = [
    `Pid=${p.id}`, `agg=${agg.agg}`, `func=${fVal}`, `scope=${sVal}`,
  ]
  
  if (agg.scope !== 'CURRENT') meta.push(`n=${Number(agg.n) || 1}`)

  // Faqat keyingi parametrga qo'llash rejimi bo‘lsa — token ichiga yozdik,
  // Global bo‘lsa ham token ichida bo‘lishi xavfsiz (backend o‘qiydi).
  const tag = meta.join('|')
  result.Calculate.push(tag)
  // current.value += `[${p.name || parameterName(p)}]`
  // Ekranda ham ko'rinadigan badge matn
  const aggLabel = (aggOptions.find(x => x.value === agg.agg)?.label) || agg.agg
  // const funcLabel = (funcOptions.find(x => x.value === agg.func)?.text) || agg.func
  // let scopeLabel = 'joriy'
  // if (agg.scope === 'PREV') scopeLabel = `oldingi ${agg.n}`
  // if (agg.scope === 'ROLLING') scopeLabel = `oxirgi ${agg.n}`
 const funcLabel = (funcOptions.find(x => x.value === fVal)?.text) || fVal
 let scopeLabel = 'joriy'
 if (sVal === 'PREV')    scopeLabel = `oldingi ${agg.n}`
 if (sVal === 'ROLLING') scopeLabel = `oxirgi ${agg.n}`
  // Masalan: [Parametr nomi] ⟨Smenalik • O‘rtacha • joriy⟩
  current.value += `[${p.name || p.id}] ⟨${aggLabel} • ${funcLabel} • ${scopeLabel}⟩`
}

/* Tanlovlar */
const sel = reactive({ sex: 0, page: 0, group: 0 })

/* Tanlangan bo‘yicha computed’lar */
const pages = computed(() => items.value?.[sel.sex]?.pages ?? [])
const groups = computed(() => pages.value?.[sel.page]?.groups ?? [])
const parameters = computed(() => groups.value?.[sel.group]?.parameters ?? [])

/* Kalkulyator holati */
const logList = ref('')
const current = ref('')
const answer = ref('')
const operatorClicked = ref(true)
const result = reactive({ Calculate: [], Comment: '' })

/* Kalkulyator amallari */
function anim(id) { const tl = anime.timeline({ targets: `#${id}`, duration: 220, easing: 'easeInOutCubic' }); tl.add({ backgroundColor: '#c1e3ff' }).add({ backgroundColor: '#f4faff' }) }
function append(v) { if (operatorClicked.value) { current.value = ''; operatorClicked.value = false } anim(typeof v === 'string' ? `n${v}` : 'nX'); result.Calculate.push(String(v)); current.value += String(v) }
function addOp(op, id) { anim(id); if (!operatorClicked.value) { logList.value += `${current.value} ${op} `; current.value = ''; operatorClicked.value = true; result.Calculate.push(op) } }
const clear = () => { addOp('', 'clear'); logList.value = ''; current.value = ''; answer.value = ''; result.Calculate = []; operatorClicked.value = false }
const sign = () => addOp('±', 'sign')
const percent = () => addOp('%', 'percent')
const dot = () => { if (!current.value.includes('.')) append('.') }
const plus = () => addOp('+', 'plus')
const minus = () => addOp('-', 'minus')
const times = () => addOp('*', 'times')
const divide = () => addOp('/', 'divide')
const equal = () => { addOp('=', 'equal'); const expr = result.Calculate.map(t => (t.startsWith('Pid=') || t.startsWith('Static=')) ? '1' : t).join(''); try { answer.value = eval(expr) } catch { answer.value = 'Error' } }

/* Parametr/Static qo‘shish */
// function appendParam(p){ result.Calculate.push(`Pid=${p.id}`); current.value += `[${p.name || p.id}]` }

/* Tanlov funksiyalari */
function selectSex(i) { sel.sex = i; sel.page = 0; sel.group = 0 }
function selectPage(j) { sel.page = j; sel.group = 0 }
function selectGroup(k) { sel.group = k }
  console.log(props);

/* Daraxtni yuklash */
const loadedOnce = ref(false)      // shu sessiyada yukladikmi?
const loading = ref(false)         // hozir so‘rov ketayaptimi?

async function loadTree () {
  if (loading.value) return
  loading.value = true
  try {
    const id = props.parameter?.id ?? props.parameter
    const { data } = await axios.get(`/calculator-structure/${id}`)
    items.value = Array.isArray(data.items) ? data.items : []
    loadedOnce.value = true
  } catch (e) {
    console.error(e)
    init({ message: 'Strukturani yuklashda xatolik', color: 'danger' })
  } finally {
    loading.value = false
  }
}

/* Saqlash */
async function emitSave () {
  const payload = {
    page_id_blog:props.parameter.id,
    doc_id: props.docId,
    param_id: props.parameter?.ParameterID,   // MUHIM: qaysi parametr
    sex_id: props.parameter?.sexId,
    page_id: props.parameter?.pageId,
    group_id: props.parameter?.groupId,

    tokens: [...result.Calculate],     // formula tokenlari
    comment: result.Comment || null,
  }

   try {
    const { data } =await axios.post('/svodkaFormula', payload);
    if (data.status === 200) {
      loadTree()
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
  // yoki sizdagi eski endpoint: await axios.post('/calculator', payload)
try {
    const { data } = await axios.get(`/calculator-structure/${props.parameter.id}`)
    items.value = Array.isArray(data.items) ? data.items : []
    // agar items bo‘sh bo‘lsa, computed’lar bo‘sh qoladi — UI ham shuni ko‘rsatadi
  } catch (e) {
    console.error(e)
    init({ message: 'Strukturani yuklashda xatolik', color: 'danger' })
  }
  emit('save', payload)        // ota sahifaga xabar
  openLocal.value = false
}


/* Lifecycle */
onMounted(loadTree)
/* Modal OCHILGANDAgina yukla */
watch(openLocal, async (isOpen) => {
  if (isOpen && !loadedOnce.value) {
    await loadTree()
  }
})
watch(
  () => [props.docId, props.parameter?.id ?? props.parameter],
  () => { loadedOnce.value = false }
)
</script>

<style scoped>
.calculator {
  display: grid;
  grid-template-rows: repeat(7, minmax(60px, auto));
  grid-template-columns: repeat(4, 60px);
  grid-gap: 12px;
  padding: 35px;
  font-family: "Poppins";
  font-size: 18px;
  background: #fff;
  box-shadow: 0 3px 50px -30px #4f6270;
  border: 1px solid #154ec1
}

.btn,
.zero {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background: #f4faff;
  color: #484848
}

.display,
.answer {
  grid-column: 1/5;
  display: flex;
  align-items: center
}

.display {
  color: #a3a3a3;
  border-bottom: 1px solid #154ec1;
  margin-bottom: 15px;
  overflow: hidden
}

.answer {
  font-weight: 600;
  color: #146080;
  font-size: 40px;
  height: 50px
}

.zero {
  grid-column: 1/3
}

.operator {
  background: #d9efff;
  color: #3fa9fc
}
</style>
