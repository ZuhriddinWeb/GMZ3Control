<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="exportToExcel"  class="w-14 h-12 mt-1 mr-1" icon="document_scanner">
        </VaButton>
      </div>
    </main>
    <main class="flex-grow">
    <DxDataGrid ref="dataGridRef" :data-source="rowData" showBorders="true" rowAlternationEnabled="true"
      :selectedRowKeys="selectedRowKeys" @selection-changed="onSelectionChanged" columnAutoWidth="true"
      showColumnLines="true" showRowLines="true" wordWrapEnabled="true"
      :pager="{ visible: true, showPageSizeSelector: true, allowedPageSizes: [5, 10, 20], showInfo: true }"
      keyExpr="id">
      <!-- <DxSelection mode="multiple" showCheckBoxesMode="always" /> -->

      <!-- Export moduli -->
      <!-- <DxExport enabled="true" /> -->

      <!-- FactoryStructureID bo'yicha row group -->
      <DxColumn dataField="FactoryStructureID" caption="Sex nomi" groupIndex="0" />

      <!-- ID va boshqa maydonlar -->
      <DxColumn dataField="FactoryStructureID" caption="Factory ID" width="50" />
      <DxColumn dataField="PName" caption="Nomlanishi"  />
      <DxColumn dataField="Value" caption="Qiymati" />
    </DxDataGrid>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { DxDataGrid, DxColumn, DxSelection, DxExport } from 'devextreme-vue/data-grid';
import { exportDataGrid } from 'devextreme/excel_exporter';
import { saveAs } from 'file-saver';
import ExcelJS from 'exceljs';
import { VaButton } from 'vuestic-ui';

const rowData = ref([]);
const selectedRowKeys = ref([]);
const dataGridRef = ref(null); // DxDataGrid uchun ref yaratamiz

// Ma'lumotlarni yuklash
const fetchData = async () => {
  try {
    const response = await axios.get(`/documents/${store.state.user.id}`);
    rowData.value = response.data['data'];
    console.log(rowData.value);
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

// Tanlangan satrlar o‘zgarganda chaqiriladigan funksiya
const onSelectionChanged = ({ selectedRowKeys: newSelectedRowKeys }) => {
  selectedRowKeys.value = newSelectedRowKeys;
  console.log("Selected row keys:", selectedRowKeys.value);
};

// Excelga eksport qilish funksiyasi
const exportToExcel = () => {
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet('Exported Data');

  exportDataGrid({
    component: dataGridRef.value.instance, // DataGrid instance beramiz
    worksheet: worksheet,
    autoFilterEnabled: true,
  }).then(() => {
    workbook.xlsx.writeBuffer().then((buffer) => {
      saveAs(
        new Blob([buffer], { type: "application/octet-stream" }),
        "ExportedData.xlsx"
      );
    });
  });
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
/* Qator balandligi va paddinglarni sozlash */
.dx-datagrid .dx-row {
  height: 10px; /* Qator balandligini belgilash */
}

.dx-datagrid .dx-data-row td {
  padding: 4px; /* Qatorlar uchun paddingni kamaytirish */
}

.dx-datagrid .dx-header-row th {
  padding: 6px 8px; /* Sarlavha uchun yuqori va pastki paddingni kamaytirish */
  font-weight: 500; /* Sarlavha shriftini sozlash */
}
</style>
