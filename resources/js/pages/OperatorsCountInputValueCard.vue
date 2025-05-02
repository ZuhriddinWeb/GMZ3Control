<template>
  <div class="grid grid-rows-[55px,1fr] bg-stone-100">
    <div class="flex flex-row-reverse justify-between w-full">
      <div class="w-[200] mt-28 mr-2">
        <VueShiftCalendar v-model="day" class=" w-full  mr-2  shadow-sm">
          <VaInput v-model="formatted" label="Smena" readonly />
        </VueShiftCalendar>
      </div>

      <div class="flex-1 w-5/6">
        <div
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 my-6 shadow-sm border border-slate-200 mt-24 p-4">
          <div v-for="(card, index) in rowData" @click="handleCardClick(card.id)" :key="index"
            :class="['relative p-4 border-2 bg-white shadow-lg border-slate-300 cursor-pointer rounded transition-opacity duration-300', card.loading ? 'opacity-60 pointer-events-none' : 'opacity-100']">
            <div v-if="card.loading" class="absolute inset-0 flex justify-center items-center bg-white/80 z-10 rounded">
              <VaProgressCircle indeterminate size="medium" />
            </div>
            <!-- Card title -->
            <h5 class="mb-2 text-slate-800  font-semibold flex items-center ">
              <span class="material-symbols-outlined w-1">
                factory
              </span>
              <span class="block flex-grow leading-none w-5/6" v-html="formatName(card)"></span>
              <!-- <span class="flex-grow leading-none w-5/6 truncate-text">{{ locale === 'ru' ? card.NameRus : card.Name }}</span> -->
            </h5>

            <div class="flex justify-between">
              <div class="flex justify-between flex-col">

                <div class="flex justify-end">
                  <div>
                    <VaButton @click="goToCardDetail(card.id)" preset="primary" icon="va-warning" class="mr-2  mt-8"
                      round border-color="primary">

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
                      class="absolute top-2 right-2 w-20 grid grid-cols-[min-content,1fr] gap-x-2 gap-y-1 text-xl font-semibold text-right">
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
import { ref, reactive, onMounted, provide, computed, onUnmounted, watch } from 'vue';
import axios from 'axios';

import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaCard } from 'vuestic-ui';
const { init } = useToast();
const { locale, t } = useI18n();
import { VueShiftCalendar } from 'vue-shift-calendar';
const day = ref({ smena: null, day: null });
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
const formatted = computed(() => {
  return day.value.day ? `${day.value.day} - ${day.value.smena}` : ''
});
function formatName(card) {
  const name = locale.value === 'ru' ? card.NameRus : card.Name;
  const words = name.split(' ');
  const grouped = [];

  for (let i = 0; i < words.length; i += 2) {
    grouped.push(words.slice(i, i + 2).join(' '));
  }

  return grouped.join('<br>');
}
watch(day, async (newVal) => {

  if (newVal.day && newVal.smena) {
    // 🔄 Barcha cardlarga loading flagni qo‘yib chiqamiz
    rowData.value.forEach(card => {
      card.loading = true;
    });

    await updateChart();
  }
}, { deep: true });

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
  const selectedDate = day.value.day;
  const selectedSmena = day.value.smena;

  if (!selectedDate || !selectedSmena) return;

  totalFormula.value = 0;
  totalManual.value = 0;

  if (selectedCardId.value) {
    const { data } = await axios.get(`/getRowPageResult/${selectedCardId.value}`, {
      params: { day: selectedDate, smena: selectedSmena }
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
    const { data } = await axios.get('/structure');
    for (const sex of data) {
      const res = await axios.get(`/getRowPageResult/${sex.id}`, {
        params: { day: selectedDate, smena: selectedSmena }
      });

      const total = res.data.reduce((sum, row) => sum + row.multiplied_parameter_count, 0);
      const filled = res.data.reduce((sum, row) => sum + row.kiritilgan, 0);
      const formula = res.data.reduce((sum, row) => sum + row.multiplied_formula_count, 0);
      const manual = res.data.reduce((sum, row) => sum + row.multiplied_manual_count, 0);
      const notFilled = total - filled;

      totalFormula.value += formula;
      totalManual.value += manual;

      const card = rowData.value.find(c => c.id === sex.id);
      if (card) {
        card.loading = true;
        card.filled = filled;
        card.notFilled = notFilled;
        card.formula = formula;
        card.manual = manual;
        card.loading = false;
      }
    }
  }
}


let refreshInterval = null;
onMounted(async () => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }

  // ✅ Bugungi sanani va smenani olish
  const now = new Date();
  const hour = now.getHours();
  const today = now.toISOString().split('T')[0]; // '2025-05-01' ko‘rinishida
  const smenaNow = hour >= 8 && hour < 20 ? 1 : 2;

  // ✅ Faqat tanlanmagan bo‘lsa, to‘ldiramiz
  if (!day.value.day || !day.value.smena) {
    day.value = {
      day: today,
      smena: smenaNow
    };
  }

  try {
    const structureIds = store.state.user.structure_id;

    if (Array.isArray(structureIds) && structureIds.length === 1) {
      const singleStructureId = structureIds[0];
      router.push({ name: 'OperatorDetail', params: { id: singleStructureId } });
    } else if (Array.isArray(structureIds) && structureIds.length > 1) {
      const response = await axios.get(`/structures/${structureIds.join(',')}`);
      rowData.value = response.data.map(item => ({
        ...item,
        filled: 0,
        notFilled: 0,
        formula: 0,
        manual: 0,
        loading: true
      }));
    } else {
    }
  } catch (error) {
    console.error('Error fetching data:', error);
  }

  await updateChart();

  refreshInterval = setInterval(() => {
    updateChart();
  }, 600000);
});



const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
});
onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>


<style>
.truncate-text {
  display: inline-block;
  max-width: calc(100% - 10px);
  /* icon va oraliq uchun */
  overflow-wrap: break-word;
  /* so‘z bo‘yicha bo‘lish */
  word-break: break-word;
  /* so‘z ichidan ham bo‘lishi mumkin */
  white-space: normal;
  /* bitta qatorda turmasin */
  line-height: 1.2;
}

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

.calendar-container {
  min-height: 100%;
  /* chiziq bo‘ylab to‘liq chiqishi uchun */
}

.container {
  display: flex;
  justify-content: center;
}
</style>