<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Agar rowData mavjud bo'lsa, kartalarni ko'rsat -->
    <div v-if="rowData.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-6 shadow-sm border border-slate-200 mt-24 p-4">
      <div v-for="(card, index) in rowData" :key="index" class="p-4 border-2 bg-white shadow-lg border-slate-400" :style="{ backgroundImage: `url('/bgCard.png')`,backgroundSize: 'cover', backgroundPosition: 'center' }">
        <h5 class="mb-2 text-slate-800 text-xl font-semibold flex items-start">
          <span class="material-symbols-outlined w-1">circles_ext</span>
          <span class="flex-grow leading-none w-5/6">
            {{ locale === 'ru' ? card.NameRus : card.Name }}
          </span>
        </h5>
        <!-- <p class="text-slate-600 leading-normal font-light">
          The place is close to Barceloneta Beach and bus stop just 2 min by walk and near to Naviglio where you can
          enjoy the main nightlife in Barcelona.
        </p> -->
        <button @click="goToCardDetail(card.id)"
          class="bg-[#154EC1] py-2 px-4 mt-6 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          type="button">
          {{ t('modals.viewPage') }}
        </button>
      </div>
    </div>

    <!-- Agar rowData bo'sh bo'lsa, xabar -->
    <div v-else class="text-center text-slate-500 mt-10">
      <p>{{ t('No data available') }}</p>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteUnitsModal from '../components/UnitsComponent/DeleteUnitsModal.vue';
import EditUnitsModal from '../components/UnitsComponent/EditUnitsModal.vue';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaCard } from 'vuestic-ui';
const { init } = useToast();
const { locale, t } = useI18n();
import { useRouter } from 'vue-router'
import store from '../store';

const router = useRouter()
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);


function goToCardDetail(cardId) {
  router.push({ name: 'vparam', params: { id: cardId } })
}
function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] });
}

function onupdated(rowNode, data) {
  rowNode.setData(data);
}

provide('ondeleted', ondeleted);
provide('onupdated', onupdated);



const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};

const fetchData = async () => {
  try {
    const response = await axios.get(`/structures/${store.state.user.structure_id}`);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};



onMounted(async () => {
  // Foydalanuvchi tilini localStorage'dan yuklash
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }

  try {
    const structureIds = store.state.user.structure_id;

    if (Array.isArray(structureIds) && structureIds.length === 1) {
      // Agar faqat bitta qiymat bo'lsa, avtomatik yo'naltirish
      const singleStructureId = structureIds[0];
      router.push({ name: 'vparam', params: { id: singleStructureId } });
    } else if (Array.isArray(structureIds) && structureIds.length > 1) {
      // Agar bir nechta qiymat bo'lsa, ma'lumotlarni olish
      const response = await axios.get(`/structures/${structureIds.join(',')}`);
      rowData.value = response.data;
    } else {
      console.warn('structure_id bo\'sh yoki noto\'g\'ri formatda');
    }
  } catch (error) {
    console.error('Error fetching data:', error);
  }
});


const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  // Save language preference to localStorage
  localStorage.setItem('locale', locale.value);
  // Refresh grid data with the new language
  fetchData();
};

const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
});
</script>


<style>
.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;

  display: inline-block;
  line-height: 1;
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;
}

.container {
  display: flex;
  justify-content: center;
}
</style>