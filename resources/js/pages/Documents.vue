<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main>
    </main>
    <main class="flex-grow">
      <div class="flex justify-between items-center mt-4">
        <VaDateInput v-model="selectedDate" class="mr-4" />
        <VaButton @click="fetchData" class="w-14 h-8 mt-1 mr-1" icon="search">
          Qidiruv
        </VaButton>
        <VaButton @click="exportToExcel" class="w-14 h-8 mt-1 mr-1" icon="document_scanner">
          Excel
        </VaButton>
      </div>
      <!-- <DxDataGrid ref="dataGridRef" :data-source="rowData" showBorders="true" rowAlternationEnabled="true"
        :selectedRowKeys="selectedRowKeys" @selection-changed="onSelectionChanged" columnAutoWidth="true"
        showColumnLines="true" showRowLines="true" wordWrapEnabled="true"
        :pager="{ visible: true, showPageSizeSelector: true, allowedPageSizes: [5, 10, 20], showInfo: true }"
        keyExpr="id">
        <DxColumn dataField="FactoryStructureID" caption="Sex nomi" groupIndex="0" />
        <DxColumn dataField="StartTime" caption="Vaqti" width="100" />
        <DxColumn dataField="PName" caption="Nomlanishi" />
        <DxColumn dataField="PName" caption="Nomlanishi" />
        <DxColumn dataField="Value" caption="Qiymati" />
      </DxDataGrid> -->
    </main>
  </div>
</template>



<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { DxDataGrid, DxColumn } from 'devextreme-vue/data-grid';
import { exportDataGrid } from 'devextreme/excel_exporter';
import { saveAs } from 'file-saver';
import ExcelJS from 'exceljs';
import { VaButton } from 'vuestic-ui';

const rowData = ref([]);
const selectedRowKeys = ref([]);
const dataGridRef = ref(null);

// Kunlar oralig‘i uchun date input qiymatlari
// const startDate = ref(new Date().toISOString().split('T')[0]); // Bugungi kun
// const endDate = ref(new Date().toISOString().split('T')[0]); // Bugungi kun
const selectedDate = ref(new Date().toISOString().split('T')[0]); // Boshlang‘ich qiymat bugungi sana

// Ma'lumotlarni yuklash
const fetchData = async () => {
  try {
    const response = await axios.get(`/document/${store.state.user.id}/${selectedDate.value}`);
    rowData.value = response.data.data;
    // console.log("Fetched data:", rowData.value);
  } catch (error) {
    console.error("Error fetching data:", error);
  }
};

// Tanlangan satrlar o'zgarganda chaqiriladigan funksiya
const onSelectionChanged = ({ selectedRowKeys: newSelectedRowKeys }) => {
  selectedRowKeys.value = newSelectedRowKeys;
  console.log("Selected row keys:", selectedRowKeys.value);
};

// Excelga eksport qilish funksiyasi
const exportToExcel = () => {
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet('Exported Data');

  // Ustunlarni qo'shish
  worksheet.columns = [
    { header: 'SOAT', key: 'hour', width: 10 },
    { header: 'ZICHLIK', key: 'density', width: 15 },
    { header: '+0,15', key: 'plus_015', width: 10 },
    { header: '+0,074', key: 'plus_0074', width: 10 },
    { header: 'Tayyor sinf', key: 'ready_class', width: 15 },
  ];

  // Ma'lumotlarni to'ldirish
  rowData.value.forEach((row, index) => {
    worksheet.addRow({
      hour: index + 1, // Soatlar (masalan, 1, 2, 3...)
      density: row.Value, // Zichlik
      plus_015: Math.floor(Math.random() * 50 + 250), // Random ma'lumot
      plus_0074: Math.floor(Math.random() * 50 + 80), // Random ma'lumot
      ready_class: (Math.random() * 40 + 30).toFixed(2), // Tayyor sinf qiymati
    });
  });

  // Stil va ranglarni sozlash
  worksheet.eachRow((row, rowNumber) => {
    row.eachCell((cell, colNumber) => {
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
      if (rowNumber === 1) {
        // Sarlavhalar uchun fon rangi
        cell.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FFFF00' }, // Sariq
        };
        cell.font = { bold: true };
      } else if (rowNumber === worksheet.rowCount) {
        // "O'rtacha" qiymatlar uchun
        cell.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FF0000' }, // Qizil
        };
        cell.font = { bold: true, color: { argb: 'FFFFFF' } }; // Oq shrift
      }
    });
  });

  // O'rtacha qiymatlarni qo'shish
  const lastRow = worksheet.addRow({
    hour: 'O‘rta',
    density: (
      rowData.value.reduce((sum, row) => sum + row.Value, 0) / rowData.value.length
    ).toFixed(2),
    plus_015: '---', // Bu yerda kerakli hisob-kitoblarni qo'shishingiz mumkin
    plus_0074: '---',
    ready_class: '---',
  });

  // Excel faylini saqlash
  workbook.xlsx.writeBuffer().then((buffer) => {
    saveAs(
      new Blob([buffer], { type: 'application/octet-stream' }),
      'ExportedData.xlsx'
    );
  });
};


// Sahifa yuklanganda ma'lumotlarni yuklash
onMounted(() => {
  fetchData();
});
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
