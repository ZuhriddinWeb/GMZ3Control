<template>
  <VaModal
    v-model="modelValueLocal"
    size="large"
    title="Yangi formula yaratish"
    ok-text="Tasdiqlash"
    cancel-text="Bekor qilish"
    @ok="$emit('save', { tokens: result.Calculate, comment: result.Comment })"
    @close="close"
    close-button
  >
    <div class="flex gap-4">
      <!-- Chap: Kalkulyator -->
      <div class="calculator">
        <div class="answer">{{ answer }}</div>
        <div class="display">{{ logList + current }}</div>

        <div @click="clear"   id="clear"    class="btn operator">C</div>
        <div @click="sign"    id="sign"     class="btn operator">+/-</div>
        <div @click="percent" id="percent"  class="btn operator">%</div>
        <div @click="divide"  id="divide"   class="btn operator">/</div>

        <div @click="append('7')" id="n7"  class="btn">7</div>
        <div @click="append('8')" id="n8"  class="btn">8</div>
        <div @click="append('9')" id="n9"  class="btn">9</div>
        <div @click="times"       id="times" class="btn operator">*</div>

        <div @click="append('4')" id="n4"  class="btn">4</div>
        <div @click="append('5')" id="n5"  class="btn">5</div>
        <div @click="append('6')" id="n6"  class="btn">6</div>
        <div @click="minus"       id="minus" class="btn operator">-</div>

        <div @click="append('1')" id="n1"  class="btn">1</div>
        <div @click="append('2')" id="n2"  class="btn">2</div>
        <div @click="append('3')" id="n3"  class="btn">3</div>
        <div @click="plus"        id="plus"  class="btn operator">+</div>

        <div @click="append('0')"  id="n0"  class="zero">0</div>
        <div @click="dot"          id="dot" class="btn">.</div>
        <div @click="equal"        id="equal" class="btn operator">=</div>
        <div @click="append('(')"  id="lb" class="btn p-4">(</div>
        <div @click="append(')')"  id="rb" class="btn p-4">)</div>
      </div>

      <!-- O'ng: Sex → Sahifa → Group → Parametrlar -->
      <div class="flex-1 min-w-[420px]">
        <div class="mb-2 text-xs text-gray-500">Sex → Sahifa → Guruh</div>

        <!-- SEXLAR -->
        <div class="mb-3 flex flex-wrap gap-2">
          <VaButton
            v-for="(sid, i) in doc.FactoryStructureID"
            :key="`sex-${sid}`"
            size="small"
            :color="i === sel.sexIndex ? 'primary' : 'secondary'"
            @click="sel.sexIndex = i; sel.pageIndex = 0; sel.groupIndex = 0"
          >
            {{ structureName(sid) }}
          </VaButton>
        </div>

        <!-- SAHIFALAR -->
        <div v-if="pagesForSelected.length" class="mb-3 flex flex-wrap gap-2">
          <VaButton
            v-for="(pageNum, j) in pagesForSelected"
            :key="`page-${pageNum}`"
            size="small"
            :color="j === sel.pageIndex ? 'primary' : 'secondary'"
            @click="sel.pageIndex = j; sel.groupIndex = 0"
          >
            {{ pageName(sidSelected, pageNum) }}
          </VaButton>
        </div>

        <!-- GROUPlar -->
        <div v-if="groupsForSelected.length" class="mb-3 flex flex-wrap gap-2">
          <VaButton
            v-for="(gid, k) in groupsForSelected"
            :key="`grp-${gid}`"
            size="small"
            :color="k === sel.groupIndex ? 'primary' : 'secondary'"
            @click="sel.groupIndex = k"
          >
            {{ groupName(sidSelected, pageSelected, gid) }}
          </VaButton>
        </div>

        <!-- PARAMETRLAR -->
        <div class="border rounded p-2 min-h-[160px]">
          <div v-if="paramsForSelected.length" class="flex flex-wrap gap-2">
            <VaButton
              v-for="pid in paramsForSelected"
              :key="`param-${pid}`"
              color="warning"
              text-color="black"
              :style="{ borderRadius: '0' }"
              @click="appendParam(pid)"
            >
              {{ parameterName(pid) }}
            </VaButton>
          </div>
          <div v-else class="text-gray-500 text-sm">Parametrlar topilmadi</div>
        </div>

        <!-- Statik parametrlar (ixtiyoriy) -->
        <div v-if="staticParameters.length" class="mt-3">
          <div class="mb-1 text-xs text-gray-500">Statik parametrlar</div>
          <div class="flex flex-wrap gap-2">
            <VaButton
              v-for="sp in staticParameters"
              :key="sp.id"
              color="warning"
              text-color="black"
              :style="{ borderRadius: '0' }"
              @click="appendStatic(sp)"
            >
              {{ sp.Name }}
            </VaButton>
          </div>
        </div>
      </div>
    </div>

    <VaTextarea
      class="w-full mt-3"
      v-model="result.Comment"
      :max-length="125"
      label="Izoh"
    />
  </VaModal>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { VaModal, VaButton, VaTextarea, useToast } from 'vuestic-ui'
import anime from 'animejs/lib/anime.es.js'

/* --------- Props & Emits ---------- */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  docId: { type: [Number, String], required: true },
})
const emit = defineEmits(['update:modelValue', 'save'])

/* --------- Modal local v-model ----- */
const modelValueLocal = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const close = () => emit('update:modelValue', false)

/* --------- Toast  --------- */
const { init } = useToast()

/* --------- Kalkulyator holati --------- */
const logList = ref('')
const current = ref('')
const answer = ref('')
const operatorClicked = ref(true)

const result = reactive({
  Calculate: [],   // tokenlar ketma-ketligi: 'Pid=..', 'Tid=..', '+', '(', ')', 'Static=..' va h.k.
  Comment: '',
})

/* --------- Doc’dan keladigan struktura --------- */
const doc = reactive({
  FactoryStructureID: [],
  NumberPageBlogs: [],
  GroupBlogs: [],
  ParameterBlogs: [],
})
/* Nomi uchun keshlar */
const maps = reactive({
  structures: new Map(),        // sid -> Name
  pages: new Map(),             // "sid-page" -> Name
  groups: new Map(),            // "sid-page-gid" -> Name
  parameters: new Map(),        // pid -> Name
})

/* Tanlovlar (indexlar orqali) */
const sel = reactive({ sexIndex: 0, pageIndex: 0, groupIndex: 0 })

/* --------- Helper: JSON parse safe --------- */
function safeParse(str, fallback) {
  if (!str) return fallback
  if (Array.isArray(str)) return str
  try { return JSON.parse(str) } catch { return fallback }
}

/* --------- Data yuklash --------- */
async function loadDoc() {
  // /addfordoc/{id} yoki shunga o‘xshash endpointdan daraxtni/selectedni emas,
  // DocumentNumberPage dagi rowni olib keling (sizda shu bor).
  const { data } = await axios.get(`/api/document-number-page/${props.docId}`)
  // Maydonlar string bo‘lsa JSON.parse qilamiz:
  doc.FactoryStructureID = safeParse(data.FactoryStructureID, [])
  doc.NumberPageBlogs    = safeParse(data.NumberPageBlogs, [])
  doc.GroupBlogs         = safeParse(data.GroupBlogs, [])
  doc.ParameterBlogs     = safeParse(data.ParameterBlogs, [])

  // Nomlar uchun ma’lumotlarni oldindan chaqirib, map’ga qo‘yamiz
  await preloadNames()
}

/* --------- Nomlarni preload qilish --------- */
async function preloadNames() {
  try {
    // 1) Sex nomlari
    if (doc.FactoryStructureID.length) {
      const { data } = await axios.post('/structures-bulk', { ids: doc.FactoryStructureID })
      data.forEach(s => maps.structures.set(String(s.id), s.Name))
    }
    // 2) Sahifa nomlari
    const pagePairs = []
    doc.FactoryStructureID.forEach((sid, i) => {
      (doc.NumberPageBlogs[i] || []).forEach(pn => pagePairs.push({ sid, page: pn }))
    })
    if (pagePairs.length) {
      const { data } = await axios.post('/pages-bulk', { pairs: pagePairs })
      data.forEach(p => maps.pages.set(`${p.StructureID}-${p.NumberPage}`, p.Name))
    }
    // 3) Guruh nomlari
    const groupTriples = []
    doc.FactoryStructureID.forEach((sid, i) => {
      (doc.NumberPageBlogs[i] || []).forEach((pn, j) => {
        (doc.GroupBlogs[i]?.[j] || []).forEach(gid => groupTriples.push({ sid, page: pn, gid }))
      })
    })
    if (groupTriples.length) {
      const { data } = await axios.post('/groups-bulk', { triples: groupTriples })
      data.forEach(g => maps.groups.set(`${g.StructureID}-${g.PageID}-${g.id}`, g.Name))
    }
    // 4) Parametr nomlari
    const allPids = []
    doc.ParameterBlogs.flat(3).forEach(arr => {
      if (Array.isArray(arr)) arr.forEach(pid => allPids.push(pid))
    })
    const pidSet = [...new Set(allPids)]
    if (pidSet.length) {
      const { data } = await axios.post('/parameters-bulk', { ids: pidSet })
      data.forEach(p => maps.parameters.set(p.id, p.Name))
    }
  } catch (e) {
    console.error(e)
    init({ message: 'Nomlarni yuklashda xatolik', color: 'danger' })
  }
}

/* --------- Computed: tanlanganlar uchun ro‘yxatlar --------- */
const sidSelected = computed(() => doc.FactoryStructureID[sel.sexIndex] ?? null)
const pagesForSelected = computed(() => doc.NumberPageBlogs[sel.sexIndex] ?? [])
const pageSelected = computed(() => pagesForSelected.value[sel.pageIndex] ?? null)
const groupsForSelected = computed(() => doc.GroupBlogs?.[sel.sexIndex]?.[sel.pageIndex] ?? [])
const paramsForSelected = computed(() => doc.ParameterBlogs?.[sel.sexIndex]?.[sel.pageIndex]?.[sel.groupIndex] ?? [])

/* --------- Nomi funksiya --------- */
const structureName = (sid) => maps.structures.get(String(sid)) || `Sex #${sid}`
const pageName = (sid, pn) => maps.pages.get(`${sid}-${pn}`) || `Sahifa ${pn}`
const groupName = (sid, pn, gid) => maps.groups.get(`${sid}-${pn}-${gid}`) || `Guruh #${gid}`
const parameterName = (pid) => maps.parameters.get(pid) || pid

/* --------- Statik parametrlar (ixtiyoriy) --------- */
const staticParameters = ref([])
async function fetchStaticParameters() {
  try {
    const { data } = await axios.get('/static')
    staticParameters.value = data
  } catch {}
}

/* --------- Parametrlardan token qo‘shish --------- */
function appendParam(pid) {
  result.Calculate.push(`Pid=${pid}`)
  current.value += `[${parameterName(pid)}]`
}

/* --------- Statiklardan qo‘shish --------- */
function appendStatic(sp) {
  result.Calculate.push(`Static=${sp.id}`)
  current.value += `[${sp.Name}]`
}

/* --------- Kalkulyator amallari --------- */
function animate(id) {
  const tl = anime.timeline({ targets: `#${id}`, duration: 250, easing: 'easeInOutCubic' })
  tl.add({ backgroundColor: '#c1e3ff' }).add({ backgroundColor: '#f4faff' })
}
function append(val) {
  if (operatorClicked.value) { current.value = ''; operatorClicked.value = false }
  animate(typeof val === 'string' ? `n${val}` : 'nX')
  result.Calculate.push(String(val))
  current.value += String(val)
}
function addOp(op, id) {
  const tl = anime.timeline({ targets: `#${id}`, duration: 250, easing: 'easeInOutCubic' })
  tl.add({ backgroundColor: '#a6daff' }).add({ backgroundColor: '#d9efff' })
  if (!operatorClicked.value) {
    logList.value += `${current.value} ${op} `
    current.value = ''
    operatorClicked.value = true
    result.Calculate.push(op)
  }
}
const clear  = () => { addOp('', 'clear'); logList.value=''; current.value=''; answer.value=''; result.Calculate=[]; operatorClicked.value=false }
const sign   = () => addOp('±','sign')
const percent= () => addOp('%','percent')
const dot    = () => { animate('dot'); if (!current.value.includes('.')) append('.') }
const plus   = () => addOp('+','plus')
const minus  = () => addOp('-','minus')
const times  = () => addOp('*','times')
const divide = () => addOp('/','divide')
const equal  = () => {
  addOp('=','equal')
  // Demo baholash (real hisoblashni backendda qilgan yaxshiroq)
  const expr = result.Calculate.map(t => (t.startsWith('Pid=')||t.startsWith('Static=')) ? '1' : t).join('')
  try { answer.value = eval(expr) } catch { answer.value = 'Error' }
}

/* --------- Lifecycle --------- */
onMounted(async () => {
  await Promise.all([loadDoc(), fetchStaticParameters()])
})
watch(() => props.docId, async () => { await loadDoc() })
</script>

<style scoped>
.calculator{
  display:grid; grid-template-rows:repeat(7,minmax(60px,auto));
  grid-template-columns:repeat(4,60px); grid-gap:12px; padding:35px;
  font-family:"Poppins"; font-size:18px; background:#fff; box-shadow:0 3px 50px -30px #4f6270;
  border:1px solid #154ec1
}
.btn,.zero{display:flex;align-items:center;justify-content:center;cursor:pointer;background:#f4faff;color:#484848}
.display,.answer{grid-column:1/5;display:flex;align-items:center}
.display{color:#a3a3a3;border-bottom:1px solid #154ec1;margin-bottom:15px;overflow:hidden}
.answer{font-weight:600;color:#146080;font-size:40px;height:50px}
.zero{grid-column:1/3}
.operator{background:#d9efff;color:#3fa9fc}
</style>
