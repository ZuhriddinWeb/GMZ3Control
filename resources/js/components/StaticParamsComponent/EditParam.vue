<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="openModal" />

    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchParams">
        {{ t('modals.editFactory') }}
      </h3>

      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <!-- Sex (structure) -->
          <VaSelect class="w-full" v-model="result.FactoryStructureID" :options="structureOptions" value-by="value"
            text-by="text" :label="t('menu.structure')" clearable @update:modelValue="getPages" />

          <!-- Parametr -->
          <VaSelect class="w-full" v-model="result.ParameterID" :options="params" value-by="value" text-by="text"
            searchable clearable :label="t('menu.params')" />

          <!-- Sahifa (page) -->
          <VaSelect class="w-full" v-model="result.NumberPage" :options="pageseOptions" value-by="value" text-by="text"
            clearable :label="t('table.page')" @update:modelValue="onPageOrStructureChange" />

          <!-- Guruh (shu sahifaga tegishli) -->
          <VaSelect class="w-full" v-model="result.GroupID" :options="GroupsOptions" value-by="value" text-by="text"
            searchable clearable :label="t('menu.groups')" />
          <!-- Tartib raqami -->
          <VaInput class="w-full" v-model="result.OrderNumber" :label="t('table.OrderNumber')" />

          <!-- Davr boshi/oxiri -->
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.PeriodStartDate" stateful highlight-weekend :text-input="true" />
            <VaDatePicker v-model="result.PeriodEndDate" stateful highlight-weekend :text-input="true" />
          </div>

          <!-- Period turi -->
          <VaSelect v-model="result.PeriodTypeId" class="mb-6 w-full" :label="t('menu.paramtypes')"
            :options="paramsOptions" value-by="value" text-by="text" clearable />

          <!-- Izoh -->
          <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from 'vuestic-ui'
import { useRoute } from 'vue-router'

const props = defineProps(['params'])
const { t } = useI18n()
const { init } = useToast()
const route = useRoute()

// Hozirgi sahifa (NumberPage) – row’dan yoki route’dan
const numberPage = computed(() => {
  const fromRow = props?.params?.data?.NumberPage
  if (fromRow !== undefined && fromRow !== null && fromRow !== '') {
    const n = Number(fromRow)
    return Number.isFinite(n) ? n : null
  }
  const raw = route.params.page ?? route.params.id
  const n = Number(raw)
  return Number.isFinite(n) ? n : null
})

const selectedDataEdit = ref(false)
const onupdated = inject('onupdated')

// Options
const paramsOptions = ref([])     // period types
const structureOptions = ref([])  // sexlar
const params = ref([])            // parametrlar
const pageseOptions = ref([])     // sahifalar
const GroupsOptions = ref([])     // sahifadagi guruhlar

const result = reactive({
  id: props?.params?.data?.id ?? null,
  FactoryStructureID: null,
  ParameterID: null,
  NumberPage: numberPage.value,
  GroupID: props?.params?.data?.GroupID ?? null,
  OrderNumber: '',
  Value: '',
  PeriodTypeId: '',
  PeriodStartDate: '',
  PeriodEndDate: '',
  Comment: '',
})

function openModal() {
  selectedDataEdit.value = true
  fetchParams()
}

// Sana -> YYYY-MM-DD
const formatDateLocal = (date) => {
  if (!date) return null
  const d = new Date(date)
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().split('T')[0]
}
const toNum = v => (v === '' || v === null || v === undefined ? null : Number(v))
const toStr = v => (v === null || v === undefined ? null : String(v))
function ensureSelectedOption(optsRef, value, label) {
  if (value == null) return
  const exists = (optsRef.value || []).some(o => String(o.value) === String(value))
  if (!exists) optsRef.value.unshift({ value, text: label || String(value) })
}



async function fetchParams() {
  try {
    const id = props?.params?.data?.id ?? null
    const page = numberPage.value

    const [
      ptRes,               // period types
      recRes,              // current record (JOIN bilan)
      structRes,           // structures (sexlar)
      paramsRes,           // parameters
      pagesRes,            // pages
    ] = await Promise.all([
      axios.get('/periodType'),
      id ? axios.get(`/static/${id}`) : Promise.resolve({ data: {} }),
      axios.get('/structure'),
      axios.get('/param'),
      axios.get('/pages-select/22'),
    ])

    // 1) Options (nom bilan)
    paramsOptions.value = (ptRes.data || []).map(p => ({
      value: toNum(p.id), text: p.name
    }))

    // Sexlar (Number)
    structureOptions.value = (structRes.data || []).map(s => ({
      value: toNum(s.id), text: s.Name
    }))

    // Parametrlar (UUID/String)
    params.value = (paramsRes.data || []).map(p => ({
      value: toStr(p.Uuid ?? p.id), text: p.Name
    }))

    // Sahifalar (Number)
    pageseOptions.value = (pagesRes.data || []).map(pg => ({
      value: toNum(pg.NumberPage), text: pg.Name
    }))
    // 2) Record mapping (ID lar v-model’da qolsin)
    const rec = recRes.data || {}

    // result.Value              = rec.value ?? ''
    result.OrderNumber = rec.OrderNumber ?? rec.order_number ?? ''
    result.FactoryStructureID = toNum(rec.FactoryStructureID ?? rec.factory_structure_id)
    result.ParameterID = toStr(rec.ParameterID ?? rec.parameter_id)      // ← STRING!
    result.NumberPage = toNum(rec.NumberPage ?? rec.number_page ?? page)
    result.GroupID = rec.GroupID != null ? toNum(rec.GroupID) : null
    result.PeriodTypeId = rec.period_type_id != null ? toNum(rec.period_type_id) : ''
    result.PeriodStartDate = rec.period_start_date ? new Date(rec.period_start_date) : null
    result.PeriodEndDate = rec.period_end_date ? new Date(rec.period_end_date) : null
    result.Comment = rec.Comment ?? rec.comment ?? ''

    // 3) Guruhlar ro‘yxati — sahifa bo‘yicha
    await onPageOrStructureChange()

    // 4) Fallback’lar (agar hozirgi ID options ichida bo‘lmasa, JOIN dan kelgan nom bilan qo‘shamiz)
    ensureSelectedOption(structureOptions, result.FactoryStructureID, rec.FSName)
    ensureSelectedOption(params, result.ParameterID, rec.PName)
    ensureSelectedOption(pageseOptions, result.NumberPage, rec.PageName)
    ensureSelectedOption(GroupsOptions, result.GroupID, rec.GName)
    ensureSelectedOption(paramsOptions, result.PeriodTypeId, rec.PTName)
    if (
      result.GroupID != null &&
      !GroupsOptions.value.some(g => Number(g.value) === Number(result.GroupID))
    ) {
      GroupsOptions.value.unshift({
        value: Number(result.GroupID),
        text: rec.GName || rec.GNameRus || `Guruh #${result.GroupID} (old)`,
      })
    }
  } catch (e) {
    console.error(e)
  }
}


// Sex o‘zgarsa – sahifalarni qayta yuklash (kerak bo‘lsa)
async function getPages() {
  try {
    // Agar sex bo‘yicha sahifalar filtrlansa, shu yerda mos endpointdan oling.
    // Hozircha umumiy ro‘yxatni qayta yuklaymiz.
    const { data } = await axios.get('/pages-select/22')
    pageseOptions.value = (data || []).map(pg => ({
      value: Number(pg.NumberPage), text: pg.Name
    }))
  } catch (e) {
    console.error(e)
  }
}

// Sahifa o‘zgarsa – guruhlar ro‘yxatini yangilash
async function onPageOrStructureChange() {
  try {
    if (!result.NumberPage) {
      GroupsOptions.value = [{ value: null, text: '— Guruhsiz —' }]
      result.GroupID = null
      return
    }
    const { data } = await axios.get(`/getRowGroupWith/${result.NumberPage}`)
    GroupsOptions.value = [
      { value: null, text: '— Guruhsiz —' },
      ...data.map(g => ({ value: Number(g.id), text: g.Name })),
    ]
    // agar hozirgi tanlov ro‘yxatda bo‘lmasa – guruhsizga qaytamiz
    if (!GroupsOptions.value.some(g => g.value === result.GroupID)) {
      result.GroupID = null
    }
  } catch (e) {
    GroupsOptions.value = [{ value: null, text: '— Guruhsiz —' }]
    result.GroupID = null
  }
}

async function onSubmit() {
  try {
    const payload = {
      id: result.id,
      FactoryStructureID: result.FactoryStructureID,
      ParameterID: result.ParameterID,
      NumberPage: result.NumberPage,
      GroupID: result.GroupID,
      OrderNumber: result.OrderNumber,
      Value: result.Value,
      PeriodTypeId: result.PeriodTypeId,
      PeriodStartDate: formatDateLocal(result.PeriodStartDate),
      PeriodEndDate: formatDateLocal(result.PeriodEndDate),
      Comment: result.Comment,
    }

    const res = await axios.put('/static', payload)

    if (res && res.status >= 200 && res.status < 300) {
      const updated = res.data?.unit || res.data?.item || res.data
      onupdated?.(props.params.node, updated)
      selectedDataEdit.value = false
      init({ message: t('login.successMessage'), color: 'success' })
    } else {
      init({ message: t('validation.error'), color: 'danger' })
    }
  } catch (e) {
    console.error(e)
    init({ message: t('validation.error'), color: 'danger' })
  }
}

// Agar foydalanuvchi sahifani o‘zgartirsa – guruhlarni qayta yuklaymiz
watch(() => result.NumberPage, onPageOrStructureChange)
</script>
