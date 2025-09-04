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
import { ref, reactive, computed, watch, onMounted,nextTick } from 'vue'
import axios from 'axios'
import { VaModal, VaButton, VaTextarea, VaSelect, VaInput, useToast } from 'vuestic-ui'
import anime from 'animejs/lib/anime.es.js'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

/* Props */
const props = defineProps({
  parameter: { type: [Object, Number, String], required: true }, // parentdan keladi (row + id)
  modelValue: { type: Boolean, default: false },
  docId: { type: [Number, String], required: true },
})

const emit = defineEmits(['update:modelValue', 'save'])

/* Modal v-model */
const openLocal = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const { init } = useToast()

/* UI holati */
const items = ref([])                            // strukturadagi daraxt
const loadedOnce = ref(false)                    // daraxtni bir marta yukladikmi?
const loadingTree = ref(false)
const loadingFormula = ref(false)
const formulaId = ref(null)                      // backenddagi formula ID (edit payti kerak bo‘lishi mumkin)

/* Kalkulyator holati */
const logList = ref('')
const current = ref('')
const answer = ref('')
const operatorClicked = ref(true)
const result = reactive({ Calculate: [], Comment: '' })

/** ---------- Daraxtni faqat modal ochilganda yuklash ---------- */
async function loadTree () {
  if (loadingTree.value) return
  loadingTree.value = true
  try {
    const pageIdBlog = typeof props.parameter === 'object' ? (props.parameter.id ?? props.parameter.page_id_blog) : props.parameter
    const { data } = await axios.get(`/calculator-structure/${pageIdBlog}`)
    items.value = Array.isArray(data.items) ? data.items : []
    loadedOnce.value = true
  } catch (e) {
    console.error(e)
    init({ message: 'Strukturani yuklashda xatolik', color: 'danger' })
  } finally {
    loadingTree.value = false
  }
}

/** ---------- FORMULANI YUKLASH (EDIT) ---------- */
function findParamNameById(guid) {
  // items daraxtidan parametr nomini topib beradi
  for (const sex of items.value || []) {
    for (const page of sex.pages || []) {
      for (const group of page.groups || []) {
        for (const p of group.parameters || []) {
          if (String(p.id) === String(guid)) return p.name || guid
        }
      }
    }
  }
  return guid
}

function prettyFromTokens(tokens=[]) {
  // Display uchun chiroyli matn: [Param nomi] ⟨Agg • Func • Scope⟩ yoki oddiy belgi/son
  const chunks = []
  for (const tok of tokens) {
    if (typeof tok === 'string' && tok.startsWith('Pid=')) {
      const parts = tok.split('|')        // Pid=GUID|agg=DAY|func=VALUE|scope=CURRENT|n=2...
      const guid = parts.find(x=>x.startsWith('Pid='))?.split('=')[1]
      const agg  = parts.find(x=>x.startsWith('agg='))?.split('=')[1]
      const func = parts.find(x=>x.startsWith('func='))?.split('=')[1]
      const scope= parts.find(x=>x.startsWith('scope='))?.split('=')[1]
      const n    = parts.find(x=>x.startsWith('n='))?.split('=')[1]
      const name = findParamNameById(guid)
      const scopeLabel = scope === 'PREV' ? `oldingi ${n}` : scope === 'ROLLING' ? `oxirgi ${n}` : 'joriy'
      chunks.push(`[${name}] ⟨${agg ?? ''} • ${func ?? ''} • ${scopeLabel}⟩`)
    } else {
      chunks.push(String(tok))
    }
  }
  return chunks.join(' ')
}

async function loadExistingFormula() {
  if (loadingFormula.value) return
  loadingFormula.value = true
  try {
    // param GUID row ichida bo‘lishi kerak (ParameterID). Yo‘q bo‘lsa parentdan jo‘natib qo‘ying.
    const paramGuid = typeof props.parameter === 'object' ? props.parameter.ParameterID : null

    if (!paramGuid) { loadingFormula.value = false; return }
    const { data } = await axios.get('/svodkaFormula/by-param', {
      params: { doc_id: props.docId, param_id: paramGuid },
    })
    // kutilgan format: { id, tokens: string[], comment: string }
    if (data) {
      formulaId.value = data.id ?? null
      result.Calculate = Array.isArray(data.tokens) ? [...data.tokens] : []
      result.Comment = data.comment ?? ''
      // display’ni to‘ldiramiz
      current.value = ''
      logList.value = prettyFromTokens(result.Calculate)
      operatorClicked.value = true
    }
  } catch (e) {
    // formulasi bo‘lmasa jim
    console.warn('Formula topilmadi yoki yuklab bo‘lmadi', e)
  } finally {
    loadingFormula.value = false
  }
}
async function emitSave () {
  const payload = {
    page_id_blog: (typeof props.parameter === 'object' ? props.parameter.id : props.parameter),
    doc_id: props.docId,
    param_id: typeof props.parameter === 'object' ? props.parameter?.ParameterID : null,
    sex_id:   props.parameter?.sexId,
    page_id:  props.parameter?.pageId,
    group_id: props.parameter?.groupId,
    tokens:   [...result.Calculate],
    comment:  result.Comment || null,
    // Agar backend edit va create ni farqlasa:
    // formula_id: formulaId.value, 
  }

  try {
    const { data } = await axios.post('/svodkaFormula', payload)
    if (data?.status === 200) {
      init({ message: t('login.successMessage'), color: 'success' })
      emit('save', payload)
      openLocal.value = false
    } else {
      console.error('Save error:', data)
      init({ message: t('errors.saveFailed'), color: 'danger' })
    }
  } catch (e) {
    console.error(e)
    init({ message: t('errors.saveFailed'), color: 'danger' })
  }
}
/** ---------- Modal ochilganda: avval daraxt, so‘ng formula ---------- */
watch(
  () => props.modelValue,                  // openLocal emas, asl prop’ni kuzatamiz
  async (isOpen) => {
    if (!isOpen) return

    // parent set qilgan parameter to‘liq kelib olishiga imkon bering
    await nextTick()

    if (!loadedOnce.value) {
      await loadTree()
    }
    await loadExistingFormula()
  }
)
watch(
  () => [props.parameter?.id ?? props.parameter, props.docId],
  () => { loadedOnce.value = false }       // keyingi ochishda loadTree qayta ishlaydi
)
onMounted(async () => {
  if (props.modelValue) {
    await nextTick()
    if (!loadedOnce.value) await loadTree()
    await loadExistingFormula()
  }
})


/* … sizdagi kalkulyator funksiyalari (append, addOp, equal, …) aynan qoladi … */

/** ---------- Saqlash ---------- */

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
