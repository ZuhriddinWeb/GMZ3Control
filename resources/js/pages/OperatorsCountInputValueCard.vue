<template>
  <div class="grid grid-rows-[55px,1fr]">
    <div class="flex justify-between">
      <div class="w-4/6">
        <div
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 my-6 shadow-sm border border-slate-200 mt-24 p-4">
          <div v-for="(card, index) in rowData" @click="handleCardClick(card.id)" :key="index"
            class="relative p-4 border-2 bg-white shadow-xl border-slate-400 cursor-pointer">

            <!-- Card title -->
            <h5 class="mb-2 text-slate-800 text-xl font-semibold flex items-start ">
              <span class="material-symbols-outlined w-1 mr-3">circles_ext</span>
              <span class="flex-grow leading-none w-5/6">{{ locale === 'ru' ? card.NameRus : card.Name }}</span>
            </h5>

            <div class="flex justify-between">
              <div class="flex justify-between flex-col">

                <div class="flex justify-end">
                  <div>
                    <VaButton @click="goToCardDetail(card.id)" preset="primary" icon="va-warning" class="mr-2  mt-8" round
                      border-color="primary">
                     
                    </VaButton>
                    <VaButton @click="goToCardDetailInputed(card.id)" round icon="va-check" class="mr-2  mt-8"
                      color="success">
                    </VaButton>
                    <VaButton @click="goToCardDetailNotInputed(card.id)" round icon="clear" class="mr-2  mt-8"
                      color="danger">
                    </VaButton>
                  </div>
                  <div>
                    <!-- Card title -->
                    <!-- Card title -->
                    <div
                      class="absolute top-2 right-2 w-24 grid grid-cols-[min-content,1fr] gap-x-2 gap-y-1 text-xl font-semibold text-right">
                      <span class="text-green-600 material-symbols-outlined ">edit</span>
                      <span class="text-green-600 text-base">{{ card.filled }}</span>
                        
                      <span class="material-symbols-outlined text-red-600">warning</span>
                      <span class="text-red-600 text-base">{{ card.notFilled }}</span>

                      <span class="material-symbols-outlined text-blue-600">support_agent</span>
                      <span class="text-blue-600 text-base">{{ card.manual }}</span>

                      <span class="material-symbols-outlined text-teal-600">function</span>
                      <span class="text-teal-600 text-base">{{ card.formula }}</span>
                    </div>

                    <!-- <div class="absolute right-10 flex gap-3">
                      <VaButton @click="goToCardDetailInputed(card.id)" round icon="va-check" class="mr-2  mt-8"
                        color="success">
                      </VaButton>
                      <VaButton @click="goToCardDetailNotInputed(card.id)" round icon="clear" class="mr-2  mt-8"
                        color="danger">
                      </VaButton>

                    </div> -->
                  </div>
                </div>
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
const filterType = computed(() => route.query.type); // 'inputed' yoki 'not_inputed'
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
function goToCardDetailInputed(cardId) {
  router.push({ name: 'OperatorDetail', params: { id: cardId }, query: { type: 'inputed' } });
}

function goToCardDetailNotInputed(cardId) {
  router.push({ name: 'OperatorDetail', params: { id: cardId }, query: { type: 'not_inputed' } });
}

const filteredData = computed(() => {
  if (filterType.value === 'inputed') {
    return allData.value.filter(item => item.Value !== null); // qiymat kiritilganlar
  } else if (filterType.value === 'not_inputed') {
    return allData.value.filter(item => item.Value === null); // qiymat yo‘q
  } else {
    return allData.value; // barcha ma’lumotlar
  }
});



const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};




const totalFormula = ref(0);
const totalManual = ref(0);

async function updateChart() {
  const now = new Date();
  const today = now.toISOString().split('T')[0];
  const hour = now.getHours();
  const smena = hour >= 8 && hour < 20 ? 1 : 2;

  totalFormula.value = 0;
  totalManual.value = 0;

  if (selectedCardId.value) {
    // Faqat bitta sex tanlangan holatda
    const { data } = await axios.get(`/getRowPageResult/${selectedCardId.value}`, {
      params: { day: today, smena }
    });

    const pageStats = data.reduce((acc, row) => {
      acc.total += row.multiplied_parameter_count;
      acc.filled += row.kiritilgan;
      acc.formula += row.multiplied_formula_count;
      acc.manual += row.multiplied_manual_count;
      return acc;
    }, { total: 0, filled: 0, formula: 0, manual: 0 });

    totalFormula.value = pageStats.formula;
    totalManual.value = pageStats.manual;

  } else {
    // Barcha sexlar bo‘yicha statistikani olish
    const { data } = await axios.get('/structure');
    const sexStats = [];

    for (const sex of data) {
      const res = await axios.get(`/getRowPageResult/${sex.id}`, {
        params: { day: today, smena }
      });

      const total = res.data.reduce((sum, row) => sum + row.multiplied_parameter_count, 0);
      const filled = res.data.reduce((sum, row) => sum + row.kiritilgan, 0);
      const formula = res.data.reduce((sum, row) => sum + row.multiplied_formula_count, 0);
      const manual = res.data.reduce((sum, row) => sum + row.multiplied_manual_count, 0);
      const notFilled = total - filled;

      // Yig‘indi qiymatlarni umumiy hisobga qo‘shamiz
      totalFormula.value += formula;
      totalManual.value += manual;

      // Har bir sex kartasiga joylaymiz
      const card = rowData.value.find(c => c.id === sex.id);
      if (card) {
        card.filled = filled;
        card.notFilled = notFilled;
        card.formula = formula;
        card.manual = manual;
      }

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
  await updateChart();
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