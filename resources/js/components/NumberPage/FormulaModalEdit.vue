<template>
  <VaModal
    v-model="openLocal"
    fullscreen
    title="Formulani tahrirlash"
    ok-text="Saqlash"
    cancel-text="Bekor qilish"
    @ok="emitSave"
    close-button
  >
    <div class="flex gap-4">
      <!-- CHAP: kalkulyator (create’dagi kabi) -->
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

      <!-- O‘NG tomon – (sex/page/group/param) daraxti, tugmalar — xuddi create’da -->
      <div class="flex-1 min-w-[420px]">
        <!-- … shu yer create modal bilan bir xil qoladi … -->
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

const props = defineProps({
  parameter: { type: Object, required: true },     // { …, ParameterID, formulaId? … }
  modelValue: { type: Boolean, default: false },
  docId: { type: [Number, String], required: true },
  initialTokens: { type: Array, default: () => [] },
  initialComment:{ type: String, default: null },
})
const emit = defineEmits(['update:modelValue', 'save'])

const openLocal = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})
const { init } = useToast()

/* Daraxtni yuklash – xuddi create’dagi kabi */
const items = ref([])
/* … aggOptions/funcOptions/scopeOptions/applyMode/agg – xuddi create’dagi kabi … */

const sel = reactive({ sex: 0, page: 0, group: 0 })
const pages = computed(() => items.value?.[sel.sex]?.pages ?? [])
const groups = computed(() => pages.value?.[sel.page]?.groups ?? [])
const parameters = computed(() => groups.value?.[sel.group]?.parameters ?? [])

/* Kalkulyator holati */
const logList = ref('')
const current  = ref('')
const answer   = ref('')
const operatorClicked = ref(true)
const result = reactive({ Calculate: [], Comment: '' })

/* === EDIT: mavjud formulani oldindan to‘ldirish === */
function hydrateFromTokens(tokens = []) {
  result.Calculate = [...tokens]
  // ko‘rinishi uchun oddiy string yasab qo‘yamiz
  current.value = result.Calculate.join(' ')
  answer.value = ''
}
watch(
  () => [props.initialTokens, props.initialComment, props.modelValue],
  ([tok, com, isOpen]) => {
    if (isOpen) {
      hydrateFromTokens(Array.isArray(tok) ? tok : [])
      result.Comment = com || ''
    }
  },
  { immediate: true }
)

/* Kalkulyator amallari – xuddi create’dagi kabi */
function anim(id) { const tl = anime.timeline({ targets: `#${id}`, duration: 220, easing: 'easeInOutCubic' }); tl.add({ backgroundColor: '#c1e3ff' }).add({ backgroundColor: '#f4faff' }) }
function append(v) { if (operatorClicked.value) { current.value=''; operatorClicked.value=false } anim(typeof v==='string'?`n${v}`:'nX'); result.Calculate.push(String(v)); current.value += String(v) }
function addOp(op,id){ anim(id); if(!operatorClicked.value){ logList.value += `${current.value} ${op} `; current.value=''; operatorClicked.value=true; result.Calculate.push(op) } }
const clear   = () => { addOp('', 'clear'); logList.value=''; current.value=''; answer.value=''; result.Calculate=[]; operatorClicked.value=false }
const sign    = () => addOp('±','sign')
const percent = () => addOp('%','percent')
const dot     = () => { if (!current.value.includes('.')) append('.') }
const plus    = () => addOp('+','plus')
const minus   = () => addOp('-','minus')
const times   = () => addOp('*','times')
const divide  = () => addOp('/','divide')
const equal   = () => { addOp('=','equal'); const expr = result.Calculate.map(t => (t.startsWith('Pid=')||t.startsWith('Static=')) ? '1' : t).join(''); try { answer.value = eval(expr) } catch { answer.value = 'Error' } }

/* Parametr token qo‘shish – create’dagi kabi meta bilan… (xohlasangiz aynan o‘sha kodni ko‘chiring) */

/* Daraxtni yuklash */
async function loadTree() {
  try {
    const { data } = await axios.get(`/calculator-structure/${167}`)
    items.value = Array.isArray(data.items) ? data.items : []
  } catch (e) {
    console.error(e)
    init({ message: 'Strukturani yuklashda xatolik', color: 'danger' })
  }
}

/* Saqlash (UPDATE) */
async function emitSave() {
  const payload = {
    id: props.parameter?.formulaId ?? undefined,  // ⚠️ muhim: update uchun
    page_id_blog: props.parameter?.id,
    doc_id: props.docId,
    param_id: props.parameter?.ParameterID,
    sex_id: props.parameter?.sexId,
    page_id: props.parameter?.pageId,
    group_id: props.parameter?.groupId,
    tokens: [...result.Calculate],
    comment: result.Comment || null,
  }

  await axios.post('/svodkaFormula', payload)
  emit('save', payload)
  openLocal.value = false
}

onMounted(loadTree)
</script>

<style scoped>
/* kalkulyator uslubi – create’dagi kabi */
.calculator{display:grid;grid-template-rows:repeat(7,minmax(60px,auto));grid-template-columns:repeat(4,60px);grid-gap:12px;padding:35px;font-family:"Poppins";font-size:18px;background:#fff;box-shadow:0 3px 50px -30px #4f6270;border:1px solid #154ec1}
.btn,.zero{display:flex;align-items:center;justify-content:center;cursor:pointer;background:#f4faff;color:#484848}
.display,.answer{grid-column:1/5;display:flex;align-items:center}
.display{color:#a3a3a3;border-bottom:1px solid #154ec1;margin-bottom:15px;overflow:hidden}
.answer{font-weight:600;color:#146080;font-size:40px;height:50px}
.zero{grid-column:1/3}
.operator{background:#d9efff;color:#3fa9fc}
</style>
