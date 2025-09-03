<template>
  <main class="h-full w-full">
    <VaButton icon="design_services" round preset="primary" @click="openModal" />

    <VaModal v-model="modalOpen" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="modalOpen = false" close-button max-width="1000px">
      <h3 class="va-h3 mb-4">{{ t('modals.editFactory') }}</h3>{{ props.params.data.NumberPage }}

      <DxDataGrid v-if="rows.length" :data-source="rows" :key-expr="'rowId'" :selected-row-keys="selectedKeys"
        @selection-changed="onSelection" show-borders row-alternation-enabled column-auto-width word-wrap-enabled
        :pager="pagerCfg">
        <DxSelection mode="multiple" show-check-boxes-mode="always" />
        <DxColumn data-field="sexName" :caption="t('form.structureName')" :group-index="0" />
        <DxColumn data-field="pageName" :caption="t('form.numberpage')" :group-index="1" />
        <DxColumn data-field="groupName" :caption="t('menu.groups')" :group-index="2" />
        <DxColumn data-field="paramName" :caption="t('form.name')" />
      </DxDataGrid>
    </VaModal>
  </main>
</template>

<script setup>
/* ---------- imports ---------- */
import { ref, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { VaButton, VaModal, useToast } from 'vuestic-ui'
import {
  DxDataGrid, DxColumn, DxSelection,
} from 'devextreme-vue/data-grid'

/* ---------- i18n / toast ----- */
const { t } = useI18n()
const { init } = useToast()

/* ---------- props / emit ----- */
const props = defineProps(['params'])
const onupdated = inject('onupdated')

/* ---------- state ------------ */
const modalOpen = ref(false)
const rows = ref([])       // tekis qatorlar (DataGrid manbasi)
const selectedKeys = ref([])       // parametr GUID’lari (checkbox)

/* ---------- pager ------------ */
const pagerCfg = {
  visible: true, showInfo: true, showPageSizeSelector: true,
  allowedPageSizes: [10, 20, 50]
}

/* ================================================================ */
/*  Modal ochish: backend daraxtni tekis satrlarga aylantiramiz     */
/* ================================================================ */
const openModal = async () => {
  modalOpen.value = true
  selectedKeys.value = []

  try {
    const { data } = await axios.get(`/addfordoc/${props.params.data.NumberPage}`)

    rows.value = flattenTreeToRows(data.tree)
    selectedKeys.value = flattenSelected(data.selected ?? [])
  } catch (err) {
    console.error(err)
    init({ message: 'Maʼlumotni olishda xatolik', color: 'danger' })
  }
}

// Nested arrayni flatten qilish
function flattenSelected(selected) {
  return selected.flat(3) // 3 - chunki selected 4-level nested
}




/* --------------------------------------------------------------- */
/*  Tree → flat rows                                               */
/* --------------------------------------------------------------- */
function flattenTreeToRows(tree) {
  const out = []

  tree.forEach(sex => {
    const sexKey = sex.key // sex-<sid>
    const sexTitle = sex.title
    const sexId = parseInt(sexKey.split('-')[1]) // sex-1 => 1

    sex.children?.forEach(page => {
      const pageTitle = page.title
      const pageKey = page.key // page-<sid>-<pageNum>
      const pageId = parseInt(pageKey.split('-')[2]) // page-1-100 => 100


      page.children?.forEach(group => {
        const groupTitle = group.title
        const groupKey = group.key // grp-<sid>-<pageNum>-<groupId>
       const groupId = parseInt(groupKey.split('-')[3])

        if (group.children?.length) {
          group.children.forEach(param => {
            out.push({
              rowId     : param.ParameterID ?? `param-${param.key}`,
              sexName   : sexTitle,
              sexId     : sexId,
              pageName  : pageTitle,
              pageId    : pageId,
              groupName : groupTitle,
              groupId   : groupId,
              paramName : param.title,
              ParameterID: param.ParameterID,
            })
          })
        }
      })
    })
  })

  return out
}



/* --------------------------------------------------------------- */
/*  Checkbox tanlovi                                               */
/* --------------------------------------------------------------- */
const onSelection = ({ selectedRowKeys }) => {
  selectedKeys.value = selectedRowKeys
}

/* --------------------------------------------------------------- */
/*  Saqlash (OK)                                                    */
/* --------------------------------------------------------------- */
const onSubmit = async () => {
  const selectedRows = rows.value.filter(r => selectedKeys.value.includes(r.rowId))

  // FactoryStructureID unique sexName lar asosida (sizda ID bo‘lsa sexID ni saqlang)
const factoryStructures = [...new Set(
  selectedRows.map(r => r.sexId)
)]

const numberPageBlogs = factoryStructures.map(sexId => {
  const pages = selectedRows
    .filter(r => r.sexId === sexId)
    .map(r => r.pageId)
  return [...new Set(pages)]
})

const groupBlogs = factoryStructures.map(sexId => {
  const sexPages = selectedRows.filter(r => r.sexId === sexId)
  const uniquePages = [...new Set(sexPages.map(r => r.pageId))]

  return uniquePages.map(pageId => {
    const groups = sexPages
      .filter(r => r.pageId === pageId)
      .map(r => r.groupId)
    return [...new Set(groups)]
  })
})

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
        .filter(pid => pid)
      return params
    })
  })
})


  try {
    const { data } = await axios.post('/addfordoc', {
      doc_id: props.params.data.NumberPage,
      FactoryStructureID: factoryStructures,
      NumberPageBlogs: numberPageBlogs,
      GroupBlogs: groupBlogs,
      ParameterBlogs: parameterBlogs
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
