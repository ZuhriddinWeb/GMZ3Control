<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main>
    </main>
    <main class="flex-grow">
      <div class="flex justify-between items-center mt-4">
        <VaDateInput v-model="dateRange" :readonly="false" :format-date="formatDate" :parse-date="parseDate"
          class="mr-4" />
        <VaButton @click="fetchData" class="w-14 h-8 mt-1 mr-1" icon="search">
          Qidiruv
        </VaButton>
        <VaButton @click="exportToExcel" class="w-14 h-8 mt-1 mr-1" icon="document_scanner">
          Excel
        </VaButton>
      </div>
      <DxDataGrid ref="dataGridRef" :data-source="rowData" showBorders="true" rowAlternationEnabled="true"
        :selectedRowKeys="selectedRowKeys" @selection-changed="onSelectionChanged" columnAutoWidth="true"
        showColumnLines="true" showRowLines="true" wordWrapEnabled="true"
        :pager="{ visible: true, showPageSizeSelector: true, allowedPageSizes: [5, 10, 20], showInfo: true }"
        keyExpr="id">
        <DxColumn dataField="FactoryStructureID" caption="Sex nomi" groupIndex="0" />
        <DxColumn dataField="FactoryStructureID" caption="Factory ID" width="50" />
        <DxColumn dataField="PName" caption="Nomlanishi" />
        <DxColumn dataField="Value" caption="Qiymati" />
      </DxDataGrid>
    </main>
  </div>
</template>



<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { DxDataGrid, DxColumn } from 'devextreme-vue/data-grid';
import { exportDataGrid } from 'devextreme/excel_exporter';
import { saveAs } from 'file-saver';
import ExcelJS from 'exceljs';
import { VaDateInput, VaButton } from 'vuestic-ui';

export default {
  setup() {
    const rowData = ref([]);
    const selectedRowKeys = ref([]);
    const dataGridRef = ref(null);

    // Date range initialization
    const datePlusDay = (date, days) => {
      const d = new Date(date);
      d.setDate(d.getDate() + days);
      return d;
    };
    const dateRange = ref({
      start: new Date(),
      end: datePlusDay(new Date(), 7),
    });

    // Fetch data function
    const fetchData = async () => {
      try {
        const response = await axios.get(`/document/${store.state.user.id}/${dateRange.value.start.toISOString().split("T")[0]}/${dateRange.value.end.toISOString().split("T")[0]}`, {
          // params: {
          //   id:store.state.user.id
          //   startDate: dateRange.value.start.toISOString().split("T")[0],
          //   endDate: dateRange.value.end.toISOString().split("T")[0],
          // },
        });
        rowData.value = response.data.data;
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    };

    // Export to Excel
    const exportToExcel = () => {
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet("Exported Data");

      exportDataGrid({
        component: dataGridRef.value.instance,
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

    // Date formatting and parsing
    const formatDate = (date) => {
      return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
    };

    const parseDate = (text) => {
      const [day, month, year] = text.split("/");
      return new Date(year, month - 1, day);
    };

    // On mounted fetch initial data
    onMounted(() => {
      fetchData();
    });

    return {
      rowData,
      selectedRowKeys,
      dataGridRef,
      dateRange,
      fetchData,
      exportToExcel,
      formatDate,
      parseDate,
    };
  },
};
</script>



<style scoped>
/* Qator balandligi va paddinglarni sozlash */
.dx-datagrid .dx-row {
  height: 10px;
  /* Qator balandligini belgilash */
}

.dx-datagrid .dx-data-row td {
  padding: 4px;
  /* Qatorlar uchun paddingni kamaytirish */
}

.dx-datagrid .dx-header-row th {
  padding: 6px 8px;
  /* Sarlavha uchun yuqori va pastki paddingni kamaytirish */
  font-weight: 500;
  /* Sarlavha shriftini sozlash */
}
</style>
