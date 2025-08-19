<template>
  <VaModal v-model="isOpen" close-button  fullscreen hide-default-actions>
    <h3 class="va-h3">{{ title }}</h3>

    <!-- KPI -->
    <div class="dash-kpi">
      <div class="kpi-card">
        <div class="kcap">Yozuvlar</div>
        <div class="kval">{{ rowsFiltered.length }}</div>
      </div>
      <div class="kpi-card">
        <div class="kcap">O'rtacha</div>
        <div class="kval">{{ avgVal }}</div>
      </div>
      <div class="kpi-card">
        <div class="kcap">Minimum</div>
        <div class="kval">{{ isFinite(minVal) ? minVal : '—' }}</div>
      </div>
      <div class="kpi-card">
        <div class="kcap">Maksimum</div>
        <div class="kval">{{ isFinite(maxVal) ? maxVal : '—' }}</div>
      </div>
    </div>

    <!-- Diagrammalar -->
    <div class="dash-charts">
    </div>
    <div class="chart-card">
      <div class="ctitle">Vaqt bo'yicha qiymatlar</div>
      <Chart :highcharts="Highcharts" :options="lineOptions" />
    </div>
    <div class="chart-card">
      <div class="ctitle">Parametr turiga ko'ra o'rtacha</div>
      <Chart :highcharts="Highcharts" :options="barOptions" />
    </div>

    <div v-if="loading" class="loading">Yuklanmoqda…</div>
    <div v-else-if="!rowsFiltered.length" class="empty">Ma'lumot topilmadi</div>
  </VaModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import Highcharts from 'highcharts'
import Exporting from 'highcharts/modules/exporting'
import { Chart } from 'highcharts-vue'
// Exporting(Highcharts)

const props = defineProps({
  modelValue: { type: Boolean, default: false },  // v-model
  page: { type: Object, default: null },          // { NumberPage, Name, ... }
  day: { type: String, required: true },
  smena: { type: [String, Number], required: true },
  factoryId: { type: [String, Number], required: true },   // props.id (grafik/fabrika)
  structureId: { type: [String, Number], required: true }, // store.state.user.structure_id
})
const emit = defineEmits(['update:modelValue'])
const isOpen = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const { locale } = useI18n()

// ------ helpers
const toMinutes = (t) => {
  if (!t) return 0
  const [h, m] = String(t).split(':').map(Number)
  return (isNaN(h) || isNaN(m)) ? 0 : h * 60 + m
}
const paramType = (s='') => {
  if (/ph/i.test(s)) return 'pH'
  if (/nacn/i.test(s)) return 'NaCN'
  if (/\bAg\b/i.test(s)) return 'Ag'
  if (/\bAu\b/i.test(s)) return 'Au'
  return 'Other'
}
const avg = (arr) => arr.length ? +(arr.reduce((a,b)=>a+b,0)/arr.length).toFixed(2) : 0

// ------ state
const loading = ref(false)
const rows = ref([])     // merged params + values

const title = computed(() => props.page ? `Dashboard — ${props.page.Name}` : 'Dashboard')

// ------ fetch & merge
const fetchData = async () => {
  if (!props.page || !props.day || !props.smena) return
  loading.value = true
  try {
    const [paramsRes, valuesRes] = await Promise.all([
      axios.get(`/get-params-for-user/${props.factoryId}/${props.smena}/${props.day}/${props.page.NumberPage}`),
      axios.get(`/vparams-value/${props.structureId}/${props.day}/${props.smena}`)
    ])

    const params = Array.isArray(paramsRes.data) ? paramsRes.data : []
    const values = Array.isArray(valuesRes.data) ? valuesRes.data : []

    rows.value = params.map(p => {
      const v = values.find(x => x.TimeStr === p.GTName && x.ParametersID === p.ParametersID)
      const pName = locale.value === 'ru' ? p.PNameRus : p.PName
      return {
        ...p,
        ...(v || {}),
        PNameAny: pName,
        Type: paramType(p.PName || p.PNameRus || ''),
        ValueNum: v && v.Value != null ? Number(v.Value) : null,
        EndMin: toMinutes(p.ETime),
      }
    })
  } catch (e) {
    console.error('DashboardModal fetch error:', e)
    rows.value = []
  } finally {
    loading.value = false
  }
}

// ochilganda va parametrlar o'zgarsa qayta yukla
watch(
  [() => props.modelValue, () => props.page?.NumberPage, () => props.day, () => props.smena, () => locale.value],
  ([open]) => { if (open) fetchData() },
  { immediate: false }
)

// ------ computed (filter + charts + kpi)
const rowsFiltered = computed(() =>
  rows.value
    .filter(r => r.ValueNum != null && !Number.isNaN(r.ValueNum))
    .sort((a,b) => a.EndMin - b.EndMin || a.OrderNumber - b.OrderNumber)
)

const minVal = computed(() => {
  const arr = rowsFiltered.value.map(r => r.ValueNum)
  return arr.length ? Math.min(...arr) : NaN
})
const maxVal = computed(() => {
  const arr = rowsFiltered.value.map(r => r.ValueNum)
  return arr.length ? Math.max(...arr) : NaN
})
const avgVal = computed(() => avg(rowsFiltered.value.map(r => r.ValueNum)))

const timeCats = computed(() =>
  rowsFiltered.value.map(r => `${String(r.ETime || '').slice(0,5)} • ${r.PNameAny || ''}`)
)
const timeData = computed(() => rowsFiltered.value.map(r => r.ValueNum))

const byType = computed(() => {
  const g = {}
  rowsFiltered.value.forEach(r => {
    if (!g[r.Type]) g[r.Type] = []
    g[r.Type].push(r.ValueNum)
  })
  return Object.entries(g).map(([k, arr]) => ({ type: k, avg: avg(arr) }))
})


const lineOptions = computed(() => ({
  chart: { type: 'line', height: 320 },
  title: { text: null },
  xAxis: { categories: timeCats.value, labels: { rotation: -35 } },
  yAxis: { title: { text: 'Qiymat' } },
  tooltip: { shared: true },
  series: [{ name: 'Qiymat', data: timeData.value }]
}))




// rowsFiltered: modal ichida faqat tanlangan sahifadagi qatordan iborat

// 🔹 Har bir parametr (ParametersID) bo‘yicha o‘rtacha qiymat
const paramAgg = computed(() => {
  const g = {}
  rowsFiltered.value.forEach(r => {
    const key = r.ParametersID || r.PNameAny || 'Unknown'
    if (!g[key]) g[key] = { name: r.PNameAny || key, sum: 0, cnt: 0 }
    g[key].sum += Number(r.ValueNum)
    g[key].cnt += 1
  })
  // O'rtacha va tartiblash (yuqoridan pastga)
  return Object.values(g)
    .map(o => ({ name: o.name, avg: +(o.sum / o.cnt).toFixed(2) }))
    .sort((a, b) => b.avg - a.avg)
})

// 🔹 Ustunlar (kategoriyalar) va data
const barCats = computed(() => paramAgg.value.map(x => x.name))
const barData = computed(() => paramAgg.value.map(x => x.avg))

// 🔹 Highcharts (column) — faqat tanlangan sahifa parametrlari
const barOptions = computed(() => ({
  chart: { type: 'column', height: 320 },
  title: { text: null },
  xAxis: {
    categories: barCats.value,
    labels: { rotation: -35 }, // uzun nomlar uchun
  },
  yAxis: { title: { text: "O'rtacha qiymat" } },
  tooltip: {
    shared: true,
    pointFormat: "<b>{point.y}</b>"
  },
  series: [{ name: "O'rtacha", data: barData.value }]
}))

</script>

<style scoped>
.dash-kpi { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin:10px 0 16px; }
.kpi-card { background:#fff; border-radius:14px; padding:12px; box-shadow:0 6px 18px rgba(0,0,0,.05); }
.kcap { font-size:12px; color:#6b7280; }
.kval { font-size:26px; font-weight:700; margin-top:2px; }

.dash-charts { display:grid; grid-template-columns:1fr; gap:12px; }
@media (min-width:1024px){ .dash-charts{ grid-template-columns:1fr 1fr; } }
.chart-card { background:#fff; border-radius:14px; padding:12px; box-shadow:0 6px 18px rgba(0,0,0,.05); }
.ctitle { font-weight:600; margin-bottom:6px; }

.loading, .empty { margin-top:8px; color:#6b7280; font-size:13px; }
</style>
