<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { DxDataGrid, DxColumn, DxSelection } from 'devextreme-vue/data-grid'

const props = defineProps({
  sexId:         { type:Number, required:true },
  selectedKeys:  { type:Array,  required:true }
})
const emit  = defineEmits(['sync'])

const rows = ref([])

onMounted(async () => {
  // sahifalarni olib kelamiz
  const pages = await axios.get(`/sex/${props.sexId}/pages`).then(r=>r.data)

  for (const p of pages) {
    // har sahifa uchun guruh+param
    const [, , num] = p.key.split('-')
    const groups = await axios.get(`/sex/${props.sexId}/page/${num}`).then(r=>r.data)
    rows.value.push(...groups)
  }
})

function selChanged(e){
  emit('sync', e.selectedRowKeys)
}
</script>

<template>
  <DxDataGrid :data-source="rows" :key-expr="'key'"
              :selected-row-keys="selectedKeys"
              @selection-changed="selChanged"
              :show-borders="true">
    <DxSelection mode="multiple" show-check-boxes-mode="always"/>
    <DxColumn data-field="pageName"  :group-index="0"/>
    <DxColumn data-field="groupName" :group-index="1"/>
    <DxColumn data-field="title"     caption="Parametr"/>
  </DxDataGrid>
</template>
