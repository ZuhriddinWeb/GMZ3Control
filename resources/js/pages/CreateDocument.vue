<template>
  <div class="h-full w-full text-center content-center">
    <VaButton round icon="history_edu" preset="primary" class="mt-1" @click="openModal" />

    <VaModal max-width="45%" v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')"
      @ok="onSubmit" @close="selectedDataEdit = false" close-button>
      <h3>
        <span class="va-h3">Ruxsatlar</span>
      </h3>

      <!-- DataGrid bilan bir nechta qator tanlash -->
      <main class="flex-grow mt-8">
        <DxDataGrid :data-source="rowData" showBorders="true" rowAlternationEnabled="true"
          :selectedRowKeys="selectedRowKeys" @selection-changed="onSelectionChanged" columnAutoWidth="true"
          showColumnLines="true" showRowLines="true" wordWrapEnabled="true"
          :pager="{ visible: true, showPageSizeSelector: true, allowedPageSizes: [5, 10, 20], showInfo: true }"
          keyExpr="id"
        >
          <DxSelection mode="multiple" showCheckBoxesMode="always" />
          <DxColumn dataField="FName" caption="Sex nomi" groupIndex="0" />
          <DxColumn dataField="FactoryStructureID" caption="" width="50"  />
          <DxColumn dataField="ParametersID" caption="Parameter Id si"  />
          <DxColumn dataField="PName" caption="Parameter nomi" />
        </DxDataGrid>
      </main>
    </VaModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { DxDataGrid, DxColumn, DxSelection } from 'devextreme-vue/data-grid';
import { useI18n } from 'vue-i18n';
import { VaButton, VaModal } from 'vuestic-ui';

const props = defineProps(['params']);
const selectedDataEdit = ref(false);
const { locale, t } = useI18n();
const rowData = ref([]);
const selectedRowKeys = ref([]); // Tanlangan qatorlarning `id`larini saqlaydigan massiv
const groupedSelectedData = ref({ id: [], parameters: [] }); // Tanlangan va guruhlangan ma'lumotlar

// Modal ochilganda foydalanuvchi ma'lumotlarini yuklash
const openModal = async () => {
  selectedDataEdit.value = true;
  await fetchUserData(); // Modal ochilganda foydalanuvchi ma'lumotlarini yuklash
};

// Foydalanuvchi ma'lumotlarini olish va bazadagi qiymatlarga asoslanib qatorlarni belgilash
const fetchUserData = async () => {
  try {
    const response = await axios.get(`/getUserData/${props.params.data['id']}`);
    const userData = response.data[0];

    if (userData) {
      groupedSelectedData.value.id = userData.FactoryStructureID || [];
      groupedSelectedData.value.parameters = userData.ParametersID || [];

      selectedRowKeys.value = []; // `selectedRowKeys`ni tozalaymiz

      // Bazadagi ma'lumotlarga asoslanib `selectedRowKeys` ni to'ldirish
      groupedSelectedData.value.id.forEach((factoryId, index) => {
        const parameterGroup = groupedSelectedData.value.parameters[index] || [];
        parameterGroup.forEach((parameterId) => {
          const row = rowData.value.find(
            (item) => item.FactoryStructureID === factoryId && item.ParametersID === parameterId
          );
          if (row) {
            selectedRowKeys.value.push(row.id); // Qatorning `id` sini `selectedRowKeys` ga qo'shamiz
          }
        });
      });

      console.log("Foydalanuvchi ma'lumotlariga ko'ra tanlangan row keys:", selectedRowKeys.value);
    }
  } catch (error) {
    console.error('Foydalanuvchi ma’lumotlarini olishda xatolik:', error);
  }
};

// Umumiy ma'lumotlarni olish
const fetchData = async () => {
  try {
    const response = await axios.get('/paramsgraph');
    rowData.value = response.data;
    console.log("Olingan rowData:", rowData.value);
  } catch (error) {
    console.error('Ma’lumotlarni olishda xatolik:', error);
  }
};

// Tanlangan va guruhlangan qatorlarni bazaga saqlash
const onSubmit = async () => {
  try {
    const dataToSave = {
      id: groupedSelectedData.value.id,
      parameters: groupedSelectedData.value.parameters,
      user_id: props.params.data['id']
    };

    const response = await axios.post('/documents', dataToSave);

    if (response.status === 200) {
      console.log("Ma'lumotlar muvaffaqiyatli saqlandi:", response.data);
      alert("Ma'lumotlar muvaffaqiyatli saqlandi!");
    } else {
      console.error("Ma'lumotlarni saqlashda xatolik yuz berdi:", response);
      alert("Ma'lumotlarni saqlashda xatolik yuz berdi!");
    }
  } catch (error) {
    console.error("Xatolik yuz berdi:", error);
    alert("Xatolik yuz berdi. Ma'lumotlarni saqlash amalga oshmadi.");
  }
};

// Griddagi qatorlar tanlanganda chaqiriladigan funksiya
const onSelectionChanged = ({ selectedRowKeys: newSelectedRowKeys }) => {
  selectedRowKeys.value = newSelectedRowKeys;
  console.log("Tanlangan row keys:", selectedRowKeys.value);
  groupSelectedData();
};

// Tanlangan qatorlarni FactoryStructureID bo'yicha guruhlash
const groupSelectedData = () => {
  const grouped = {};

  selectedRowKeys.value.forEach((key) => {
    const row = rowData.value.find((item) => item.id === key);
    if (row) {
      if (!grouped[row.FactoryStructureID]) {
        grouped[row.FactoryStructureID] = { id: row.FactoryStructureID, parameters: [] };
      }
      grouped[row.FactoryStructureID].parameters.push(row.ParametersID);
    }
  });

  groupedSelectedData.value = {
    id: Object.keys(grouped).map(Number),
    parameters: Object.values(grouped).map(group => group.parameters),
  };
};

// Komponent yuklanganda ma'lumotlarni olish
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
