<template>
  <div class="grid grid-rows-[55px,1fr] bg-stone-100">
    <div class="flex justify-between w-full mt-6 px-4">
      <div class="flex flex-col w-full">
        <div class="w-full">
          <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-1 my-6 shadow-sm border border-slate-200 mt-16 p-4">
            <div v-for="(card, index) in rowData" @click="handleCardClick(card.id)" :key="index"
              :class="['relative p-1 border-2 bg-white shadow-lg border-slate-300 cursor-pointer rounded transition-opacity duration-300', card.loading ? 'opacity-60 pointer-events-none' : 'opacity-100']">
              <div v-if="card.loading"
                class="absolute inset-0 flex justify-center items-center bg-white/80 z-10 rounded">
                <VaProgressCircle indeterminate size="medium" />
              </div>
              <div class="flex justify-between">
                <div class="flex flex-col w-9/12">
                  <div>
                    <h5 class="mb-2 text-slate-800  font-semibold flex items-center p-2">
                      <span class="block flex-grow leading-none " v-html="formatName(card)"></span>
                    </h5>
                  </div>
                  <div>
                    <div class="p-2">
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
                  </div>
                </div>
                <div class="grid grid-cols-[min-content,auto] gap-x-2 gap-y-1 font-semibold m-2">
                  <span class="text-green-600 material-symbols-outlined text-lg mr-3">edit</span>
                  <span class="text-green-600">{{ card.filled }}</span>

                  <span class="text-red-600 material-symbols-outlined text-lg mr-3">warning</span>
                  <span class="text-red-600">{{ card.notFilled }}</span>

                  <span class="text-blue-600 material-symbols-outlined text-lg mr-3">support_agent</span>
                  <span class="text-blue-600">{{ card.manual }}</span>

                  <span class="text-teal-600 material-symbols-outlined text-lg mr-3">function</span>
                  <span class="text-teal-600">{{ card.formula }}</span>

                  <span class="text-purple-600 material-symbols-outlined text-lg mr-3">%</span>
                  <span class="text-purple-600">
                    {{
                      card.filled + card.notFilled > 0
                        ? Math.round((card.filled / (card.filled + card.notFilled)) * 100)
                        : 0
                    }}
                  </span>
                </div>

              </div>
            </div>

          </div>

        </div>
        <div class="p-6 bg-white border-sm border-slate-200 shadow-lg mt-2 mx-3 rounded-lg">
          <!-- <h2 class="text-lg font-semibold mb-4 text-center">Bo‘limlar bo‘yicha parametrlar holati</h2> -->
          <div style="height: 280px;">
            <canvas id="barChart"></canvas>
          </div>
        </div>
      </div>
      <div class="flex flex-col justify-between">
        <div class="w-[200] mt-20 mr-2">
          <VueShiftCalendar v-model="day" class=" w-full  mr-2  shadow-lg">
            <VaInput v-model="formatted" label="Smena" readonly />
          </VueShiftCalendar>
        </div>

        <div class="w-full mr-2 mt-80 flex justify-center">
          <canvas id="pieChart" class="w-[350px] h-[350px]"></canvas>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide, computed, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { nextTick } from 'vue';
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
import Chart from 'chart.js/auto';

let barChartInstance = null;
let pieChartInstance = null;

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

  for (let i = 0; i < words.length; i += 3) {
    grouped.push(words.slice(i, i + 3).join(' '));
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
  let total = 0;
  let filled = 0;

  const { data } = await axios.get('/structure');
  for (const sex of data) {
    const res = await axios.get(`/getRowPageResult/${sex.id}`, {
      params: { day: selectedDate, smena: selectedSmena }
    });

    const totalCount = res.data.reduce((sum, row) => sum + row.multiplied_parameter_count, 0);
    const filledCount = res.data.reduce((sum, row) => sum + row.kiritilgan, 0);
    const formula = res.data.reduce((sum, row) => sum + row.multiplied_formula_count, 0);
    const manual = res.data.reduce((sum, row) => sum + row.multiplied_manual_count, 0);
    const notFilled = totalCount - filledCount;

    total += totalCount;
    filled += filledCount;
    totalFormula.value += formula;
    totalManual.value += manual;

    const card = rowData.value.find(c => c.id === sex.id);
    if (card) {
      card.loading = true;
      card.filled = filledCount;
      card.notFilled = notFilled;
      card.formula = formula;
      card.manual = manual;
      card.loading = false;
    }
  }

  renderPieChart(filled, total); // faqat 1 marta chaqiriladi
}

  await nextTick(); // chart DOM mavjud bo'lishini kutish
  renderBarChart(); // shundan keyin chizish

}
function renderBarChart() {
  const ctx = document.getElementById('barChart');
  if (!ctx) return;

  if (barChartInstance) {
    barChartInstance.destroy(); // eski chartni tozalash
  }

  const labels = rowData.value.map(card => locale.value === 'ru' ? card.NameRus : card.Name);
  const filledData = rowData.value.map(card => card.filled);
  const notFilledData = rowData.value.map(card => card.notFilled);

  barChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: t('To‘ldirilgan'),
          data: filledData,
          backgroundColor: 'rgba(34,197,94,0.6)', // green
        },
        {
          label: t('To‘ldirilmagan'),
          data: notFilledData,
          backgroundColor: 'rgba(239,68,68,0.6)', // red
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: t('Bo‘limlar bo‘yicha parametrlar holati')
        },
        legend: {
          position: 'top'
        }
      },
      scales: {
        x: {
          ticks: {
            autoSkip: false,
            maxRotation: 90,
            minRotation: 45
          }
        },
        y: {
          beginAtZero: true
        }
      }
    }
  });
}
function renderPieChart(filledCount, totalCount) {
  const ctx = document.getElementById('pieChart');
  if (!ctx) return;

  if (pieChartInstance) {
    pieChartInstance.destroy(); // eski diagrammani o‘chiramiz
  }

  pieChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Kiritilgan', 'Kiritilmagan'],
      datasets: [{
        data: [filledCount, totalCount - filledCount],
        backgroundColor: ['#10b981', '#ef4444'], // yashil va qizil
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
        },
        title: {
          display: true,
          text: 'Umumiy parametrlar'
        }
      }
    }
  });
}
let refreshInterval = null;
onMounted(async () => {
  document.body.style.transform = 'scale(0.9)';
  document.body.style.transformOrigin = 'top left';
  document.body.style.width = '111.11%'; // (1 / 0.75) * 100
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

#barChart {
  max-height: 100%;
}
</style>