<template>
  <div class="grid grid-rows-[55px,1fr]">
    <div class="flex justify-between">
      <div class="w-4/6">
        <div
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-6 shadow-sm border border-slate-200 mt-24 p-4">
          <div v-for="(card, index) in rowData" @click="handleCardClick(card.id)" :key="index"
            class="p-4 border-2 bg-white shadow-xl border-slate-400 cursor-pointer">
            <h5 class="mb-2 text-slate-800 text-xl font-semibold flex items-start">
              <span class="material-symbols-outlined w-1">circles_ext</span>
              <span class="flex-grow leading-none w-5/6">{{ locale === 'ru' ? card.NameRus : card.Name }}</span>
            </h5>
            <div class="flex justify-between">
              <div>
                <VaButton @click="goToCardDetail(card.id)" preset="primary" class="mr-6  mt-8" round
                  border-color="primary">
                  {{ t('modals.viewPage') }}
                </VaButton>
              </div>
              <div class="flex justify-end">
                <VaButton @click="goToCardDetail(card.id)" round icon="va-check" class="mr-6  mt-8" color="success">
                </VaButton>
                <VaButton @click="goToCardDetail(card.id)" round icon="clear" class="mr-6  mt-8" color="danger">
                </VaButton>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- <div class="flex flex-col justify-between w-1/6 h-screen bg-neutral-200 mt-28">
        <div id="container" class="w-full h-28 items-start"></div>

      </div> -->
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaCard } from 'vuestic-ui';
const { init } = useToast();
const { locale, t } = useI18n();
import store from '../store';

import { useRouter } from 'vue-router'
const router = useRouter()
const rowData = ref([]);
const gridApi = ref(null);
const selectedCardId = ref(null);

function goToCardDetail(cardId) {
  router.push({ name: 'OperatorDetail', params: { id: cardId } });
}
function handleCardClick(cardId) {
  selectedCardId.value = cardId;
  updateChart(); // Pie diagramma yangilansin
}




const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};




async function updateChart() {
  const now = new Date();
  const today = now.toISOString().split('T')[0];
  const hour = now.getHours();
  const smena = hour >= 8 && hour < 20 ? 1 : 2;

  if (selectedCardId.value) {
    // Tanlangan sex bo‘yicha foiz statistikasi
    const { data } = await axios.get(`/getRowPageResult/${selectedCardId.value}`, {
      params: { day: today, smena }
    });

    const pageStats = data.reduce((acc, row) => {
      acc.total += row.multiplied_parameter_count;
      acc.filled += row.kiritilgan;
      return acc;
    }, { total: 0, filled: 0 });

    const percentage = pageStats.total > 0
      ? (pageStats.filled / pageStats.total) * 100
      : 0;

  } else {
    // Barcha sexlar bo‘yicha statistikani olib kelish
    const { data } = await axios.get('/structure'); // id lar uchun
    const sexStats = [];

    for (const sex of data) {
      const res = await axios.get(`/getRowPageResult/${sex.id}`, {
        params: { day: today, smena }
      });

      const total = res.data.reduce((sum, row) => sum + row.multiplied_parameter_count, 0);
      const filled = res.data.reduce((sum, row) => sum + row.kiritilgan, 0);
      const percent = total > 0 ? (filled / total) * 100 : 0;

      sexStats.push({
        name: locale.value === 'uz' ? sex.Name : sex.NameRus,
        y: parseFloat(percent.toFixed(2))
      });
    }
  }
}

onMounted(async () => {
  // Load language preference from localStorage
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }

  try {
    const structureIds = store.state.user.structure_id;

    if (Array.isArray(structureIds) && structureIds.length === 1) {
      // Agar faqat bitta qiymat bo'lsa, avtomatik yo'naltirish
      const singleStructureId = structureIds[0];
      router.push({ name: 'OperatorDetail', params: { id: singleStructureId } });
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