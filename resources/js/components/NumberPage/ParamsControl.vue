<template>
  <main class="h-full w-full">
    <VaButton icon="star" round preset="primary" @click="openModal" />

    <VaModal v-model="modalOpen" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="modalOpen = false" close-button max-width="1000px">
      <h3 class="va-h3 mb-4">{{ t('modals.editFactory') }}</h3>

      <DxDataGrid v-if="rows.length" :data-source="rows" :key-expr="'rowId'" :selected-row-keys="selectedKeys"
        @selection-changed="onSelection" :repaint-changes-only="true" show-borders row-alternation-enabled
        column-auto-width word-wrap-enabled :pager="pagerCfg">
        <!-- <DxSelection mode="multiple" show-check-boxes-mode="always" /> -->

        <DxColumn data-field="sexName" :caption="t('form.structureName')" :group-index="0" />
        <DxColumn data-field="pageName" :caption="t('form.numberpage')" :group-index="1" />
        <DxColumn data-field="groupName" :caption="t('menu.groups')" :group-index="2" />

        <!-- Parametr nomi + formula tugmasi -->
        <DxColumn data-field="paramName" :caption="t('form.name')" cell-template="paramCell" :min-width="320"
          :allow-sorting="false" />

        <!-- DevExtreme Vue sloti: cellTemplate uchun -->
        <template #paramCell="{ data: cell }">
          <div class="flex items-center justify-between w-full">
            <div class="flex items-center w-5/6">
              <span v-if="selectedKeys.includes(cell.data.rowId)" class="text-green-600">✅</span>
              <span>{{ cell.data.paramName }}</span>
            </div>

            <div class="flex justify-between items-center w-1/6">
              <!-- CREATE -->
              <VaButton round color="info" class="m-1" :style="{
                '--va-button-height': '30px',
                '--va-button-padding': '0',
                width: '30px',
                minWidth: '30px',
                borderRadius: '9999px',
              }" @click="openFormulaModal(cell.data, props.params.data.NumberPage)">
                <VaIcon name="calculate" size="16px" />
              </VaButton>

              <!-- EDIT -->
              <VaButton round color="primary" class="m-1" :style="{
                '--va-button-height': '30px',
                '--va-button-padding': '0',
                width: '30px',
                minWidth: '30px',
                borderRadius: '9999px'
              }" @click="onEdit(cell.data, props.params.data.NumberPage)">
                <VaIcon name="edit" size="16px" />
              </VaButton>

              <!-- DELETE -->
              <VaButton  round color="danger" class="m-1" :style="{
                '--va-button-height': '30px',
                '--va-button-padding': '0',
                width: '30px',
                minWidth: '30px',
                borderRadius: '9999px'
              }" @click="deleteFormula(cell.data)">
                <VaIcon name="delete" size="16px" />
              </VaButton>
            </div>
          </div>
        </template>
      </DxDataGrid>
    </VaModal>

    <!-- FORMULA MODAL -->
    <FormulaModalEdit v-if="editModalOpen" v-model="editModalOpen" :parameter="parameterForFormula"
      :formula="formulaText" @save="handleFormulaSave" @close="handleFormulaClose" />
    <FormulaModal v-if="formulaModalOpen" v-model="formulaModalOpen" :parameter="parameterForFormula"
      :formula="formulaText" @save="handleFormulaSave" @close="handleFormulaClose" />
  </main>
</template>

<script setup>
import { ref, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { VaButton, VaModal, useToast, VaInput } from 'vuestic-ui'
import { DxDataGrid, DxColumn, DxSelection } from 'devextreme-vue/data-grid'
import FormulaModal from './FormulaModal.vue'
import FormulaModalEdit from './FormulaModalEdit.vue'
import DeleteFormula from './DeleteFormula.vue'

const { t } = useI18n()
const { init } = useToast()

const props = defineProps(['params'])
const onupdated = inject('onupdated')

const modalOpen = ref(false)
const rows = ref([])         // parametr qatorlari
const selectedKeys = ref([]) // tanlangan rowId’lar (GUID’lar)

const pagerCfg = {
  visible: true,
  showInfo: true,
  showPageSizeSelector: true,
  allowedPageSizes: [10, 20, 50],
}

// Formula modal holati
const formulaModalOpen = ref(false)
const editModalOpen = ref(false)
const deleteModalOpen = ref(false)

const parameterForFormula = ref(null)
const formulaText = ref('')
/** --- FORMULA MODAL HOLATI --- */
function openFormulaModal(row, id) {

  parameterForFormula.value = {
    ...row,   // row ichidagi barcha maydonlarni qo‘shadi
    id: id    // alohida id ni ham qo‘shib yuboradi
  }
  formulaText.value = '' // mavjudini backenddan olib kelsangiz shu yerda set qiling
  formulaModalOpen.value = true
}
async function onEdit(row, id) {
  parameterForFormula.value = {
    ...row,
    id: id,   // ✅
    ParameterID: row.ParameterID || row.param_id || null  // ⬅️ Qo‘shing!
  }

  editModalOpen.value = true
}
// Formula saqlash (demo)
// Backendga ulashni o‘zingizning endpointingizga moslang


/* ---------------- Modalni ochish va ma’lumot yuklash ---------------- */
const openModal = async () => {
  modalOpen.value = true
  selectedKeys.value = []

  try {
    const { data } = await axios.get(`/addfordocSelect/${props.params.data.NumberPage}`)

    rows.value = flattenTreeToRows(data.tree)
    // selected massivini flatten qilib, DataGrid’ga beramiz
    selectedKeys.value = flattenSelected(data.selected)
  } catch (err) {
    console.error(err)
    init({ message: 'Maʼlumotni olishda xatolik', color: 'danger' })
  }
}
async function deleteFormula(row) {
  const paramGuid = row.ParameterID
  if (!paramGuid) return

  if (!confirm('Formulani o‘chirmoqchimisiz?')) return

  try {
    // Agar backendda doc_id kerak bo‘lsa, params bilan bering:
    // await axios.delete(`/svodkaFormula/by-param/${paramGuid}`, { params: { doc_id: props.params.data.id } })

    await axios.delete(`/svodkaFormula/${paramGuid}`)

    init({ message: t('common.deleted'), color: 'success' })

    // UI ni yangilash: shu satrga belgi qo‘yilsa olib tashlaymiz
    rows.value = rows.value.map(r =>
      r.rowId === row.rowId ? { ...r, hasFormula: false, formulaId: null } : r
    )
  } catch (e) {
    console.error(e)
    init({ message: t('errors.saveFailed'), color: 'danger' })
  }
}

// nested massivni xavfsiz flatten qilish
function flattenSelected(selected) {
  if (!Array.isArray(selected)) return []
  // selected odatda 3-4 qatlam bo‘ladi; qo‘shimcha xavfsizlik uchun 5 qatlam qildik
  return selected.flat(5).filter(Boolean)
}

/* ---------------- Tree → flat rows ---------------- */
function flattenTreeToRows(tree) {
  const out = []
  if (!Array.isArray(tree)) return out

  tree.forEach(sex => {
    const sexId = parseInt(String(sex.key).split('-')[1])
    const sexTitle = sex.title

    sex.children?.forEach(page => {
      const pageId = parseInt(String(page.key).split('-')[2])
      const pageTitle = page.title

      page.children?.forEach(group => {
        const groupId = parseInt(String(group.key).split('-')[3])
        const groupTitle = group.title

        group.children?.forEach(param => {
          out.push({
            rowId: param.ParameterID ?? `param-${param.key}`, // DevExtreme keyExpr = 'rowId'
            sexName: sexTitle,
            sexId,
            pageName: pageTitle,
            pageId,
            groupName: groupTitle,
            groupId,
            paramName: param.title,
            ParameterID: param.ParameterID, // GUID
          })
        })
      })
    })
  })
  return out
}

/* ---------------- Checkbox selection ---------------- */
const onSelection = ({ selectedRowKeys }) => {
  selectedKeys.value = selectedRowKeys
}

/* ---------------- Saqlash (POST /addfordoc) ---------------- */
const onSubmit = async () => {
  const selectedRows = rows.value.filter(r => selectedKeys.value.includes(r.rowId))

  // 1) Sexlar (FactoryStructureID)
  const factoryStructures = [...new Set(selectedRows.map(r => r.sexId))]

  // 2) Har bir sex bo‘yicha sahifalar
  const numberPageBlogs = factoryStructures.map(sexId => {
    const pages = selectedRows.filter(r => r.sexId === sexId).map(r => r.pageId)
    return [...new Set(pages)]
  })

  // 3) Har bir sex → sahifa bo‘yicha group’lar
  const groupBlogs = factoryStructures.map(sexId => {
    const sexPages = selectedRows.filter(r => r.sexId === sexId)
    const uniquePages = [...new Set(sexPages.map(r => r.pageId))]
    return uniquePages.map(pageId => {
      const groups = sexPages.filter(r => r.pageId === pageId).map(r => r.groupId)
      return [...new Set(groups)]
    })
  })

  // 4) Har bir sex → sahifa → group bo‘yicha parametr GUID’lari
  const parameterBlogs = factoryStructures.map(sexId => {
    const sexPages = selectedRows.filter(r => r.sexId === sexId)
    const uniquePages = [...new Set(sexPages.map(r => r.pageId))]
    return uniquePages.map(pageId => {
      const pageGroups = sexPages.filter(r => r.pageId === pageId)
      const uniqueGroups = [...new Set(pageGroups.map(r => r.groupId))]
      return uniqueGroups.map(groupId => {
        const params = pageGroups
          .filter(r => r.groupId === groupId)
          .map(r => r.ParameterID)
          .filter(Boolean)
        return params
      })
    })
  })

  try {
    const { data } = await axios.post('/addfordoc', {
      doc_id: props.params.data.id,
      FactoryStructureID: factoryStructures,
      NumberPageBlogs: numberPageBlogs,
      GroupBlogs: groupBlogs,
      ParameterBlogs: parameterBlogs,

    })

    if (data.status === 200) {
      onupdated?.()
      modalOpen.value = false
      init({ message: t('login.successMessage'), color: 'success' })
    } else {
      throw new Error(data.message || 'Unknown error')
    }
  } catch (err) {
    console.error(err)
    init({ message: t('errors.saveFailed'), color: 'danger' })
  }
}
</script>

<style scoped>
/* ixtiyoriy: satrlar siqiqroq ko‘rinsin */
.dx-datagrid .dx-row>td {
  padding-top: 6px;
  padding-bottom: 6px;
}

/* global style (scoped bo‘lmasin) */
.btn-xxs.va-button {
  --va-button-height: 30px;
  --va-button-padding: 0;
  width: 30px;
  min-width: 30px;
  border-radius: 9999px;
}

.btn-xxs .va-icon {
  font-size: 16px;
  width: 16px;
  height: 16px;
}
</style>
