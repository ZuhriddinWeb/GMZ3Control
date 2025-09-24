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
          <!-- Tartib raqami -->
          <VaInput class="w-full" v-model="result.OrderNumber" :label="t('table.OrderNumber')" />

          <!-- Guruh (shu sahifaga tegishli) -->
          <VaSelect class="w-full" v-model="result.GroupID" :options="GroupsOptions" value-by="value" text-by="text"
            track-by="value" :label="t('menu.groups')" searchable clearable />


          <!-- Davr boshi/oxiri -->
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.PeriodStartDate" stateful highlight-weekend :text-input="true" />
            <VaDatePicker v-model="result.PeriodEndDate" stateful highlight-weekend :text-input="true" />
          </div>

          <!-- Period turi -->
          <VaSelect v-model="result.PeriodTypeId" class="mb-6 w-full" :label="t('menu.paramtypes')"
            :options="paramsOptions" value-by="value" clearable />

          <!-- Izoh -->
          <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from 'vuestic-ui'
import { useRoute } from 'vue-router'

const props = defineProps(['params'])
const { t } = useI18n()
const { init } = useToast()
const route = useRoute()

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

console.log('numberPage =', numberPage.value)

const selectedDataEdit = ref(false)
const onupdated = inject('onupdated')

const paramsOptions = ref([])
const GroupsOptions = ref([])

const result = reactive({
  id: props?.params?.data?.id ?? null,
  Value: '',
  OrderNumber: '',
  GroupID: props?.params?.data?.GroupID ?? null,
  PeriodTypeId: '',
  PeriodStartDate: '',
  PeriodEndDate: '',
  Comment: '',
  NumberPage: numberPage.value,              // ✅ oldin numberPage()
})

function openModal() {
  selectedDataEdit.value = true
  fetchParams()
}

const formatDateLocal = (date) => {
  if (!date) return null
  const d = new Date(date)
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().split('T')[0]
}

async function fetchParams() {
  try {
    const id = props?.params?.data?.id ?? null
    const page = numberPage.value               // ✅ oldin numberPage()

    // Parallel so'rovlar (id yo‘q bo‘lsa yozuvni so‘ramaymiz)
    const ptReq = axios.get('/periodType')
    const recReq = id ? axios.get(`/static/${id}`) : Promise.resolve({ data: {} })
    const grpReq = page ? axios.get(`/getRowGroupWith/${page}`) : Promise.resolve({ data: [] })

    const [ptRes, recRes, grpRes] = await Promise.all([ptReq, recReq, grpReq])

    paramsOptions.value = (ptRes.data || []).map(p => ({ value: Number(p.id), text: p.name }))

    const rec = recRes.data || {}
    const groupRaw =
      rec.GroupID ?? rec.group_id ?? rec.groupId ?? props?.params?.data?.GroupID ?? null
    const orderRaw =
      rec.OrderNumber ?? rec.order_number ?? rec.order ?? props?.params?.data?.OrderNumber ?? ''

    result.Value = rec.value ?? ''
    result.OrderNumber = orderRaw ?? ''
    result.GroupID = groupRaw != null ? Number(groupRaw) : null
    result.PeriodTypeId = rec.period_type_id != null ? Number(rec.period_type_id) : ''
    result.PeriodStartDate = rec.period_start_date ? new Date(rec.period_start_date) : null
    result.PeriodEndDate = rec.period_end_date ? new Date(rec.period_end_date) : null
    result.Comment = rec.Comment ?? rec.comment ?? ''
    result.NumberPage = page

    const groups = grpRes.data || []
    GroupsOptions.value = groups.map(g => ({ value: Number(g.id), text: g.Name }))

    if (
      result.GroupID != null &&
      !GroupsOptions.value.some(g => g.value === Number(result.GroupID))
    ) {
      const fallbackName = rec.GroupName ?? rec.group_name ?? `Guruh #${result.GroupID}`
      GroupsOptions.value.unshift({
        value: Number(result.GroupID),
        text: `${fallbackName} (old)`,
      })
    }
  } catch (e) {
    console.error(e)
  }
}

async function onSubmit() {
  try {
    const payload = {
      id: result.id,
      Value: result.Value,
      OrderNumber: result.OrderNumber,
      GroupID: result.GroupID,
      PeriodTypeId: result.PeriodTypeId,
      PeriodStartDate: formatDateLocal(result.PeriodStartDate),
      PeriodEndDate: formatDateLocal(result.PeriodEndDate),
      Comment: result.Comment,
      NumberPage: numberPage.value,           // ✅ oldin numberPage()
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
</script>
