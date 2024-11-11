<template>
  <div class="h-full w-full text-center content-center">
    <VaButton round icon="history_edu" preset="primary" class="mt-1" @click="selectedDataEdit = true" />

    <VaModal max-width="45%" v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3>
       <span class="va-h3">123</span>  
      </h3>
    <!-- Modal for adding new elements -->
    <main>
    </main>

    <!-- DataGrid with multiple row selection -->
    <main class="flex-grow mt-8">
      <DxDataGrid
        :data-source="rowData"
        showBorders="true"
        rowAlternationEnabled="true"
        :selectedRowKeys="selectedRowKeys"
        @selection-changed="onSelectionChanged"
        columnAutoWidth="true"
        showColumnLines="true"
        showRowLines="true"
        wordWrapEnabled="true"
        :pager="{ visible: true, showPageSizeSelector: true, allowedPageSizes: [5, 10, 20], showInfo: true }"
      >
        <DxSelection mode="multiple" showCheckBoxesMode="always" />
        <DxColumn dataField="FName" caption="Sex nomi" groupIndex="0" />
        <DxColumn dataField="id" caption="ID" />
        <DxColumn dataField="PName" caption="Parameter Name" />
      </DxDataGrid>
    </main>
    </VaModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
// import 'vuestic-ui/dist/vuestic-ui.css';
import { DxDataGrid, DxColumn, DxSelection } from 'devextreme-vue/data-grid';
import { useI18n } from 'vue-i18n';
import { VaButton, VaModal } from 'vuestic-ui';

const selectedDataEdit = ref(false);

const { locale, t } = useI18n();
const rowData = ref([]);
const selectedRowKeys = ref([]); // Tanlangan satrlarning kalitlari

const fetchData = async () => {
  try {
    const response = await axios.get('/paramsgraph');
    rowData.value = response.data;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

// Satrlar tanlanganda chaqiriladigan funksiya
const onSelectionChanged = ({ selectedRowKeys: newSelectedRowKeys }) => {
  selectedRowKeys.value = newSelectedRowKeys;
  console.log(selectedRowKeys.value);
  
};

// Tanlangan satrlar ustida ishlash funksiyasi
const processSelectedRows = () => {
  console.log("Tanlangan satrlar:", selectedRowKeys.value);
  // Bu yerda tanlangan satrlarni qayta ishlash logikasini qo'shing
};

onMounted(() => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  fetchData();
});
</script>

<style>
.options {
  margin-top: 20px;
  padding: 20px;
  background-color: rgba(191, 191, 191, 0.15);
  position: relative;
}

.caption {
  font-size: 18px;
  font-weight: 500;
}

.option {
  margin-top: 10px;
}

.checkboxes-mode {
  position: absolute;
  right: 20px;
  bottom: 20px;
}

.option > .dx-selectbox {
  width: 150px;
  display: inline-block;
  vertical-align: middle;
}

.option > span {
  margin-right: 10px;
}
</style>
