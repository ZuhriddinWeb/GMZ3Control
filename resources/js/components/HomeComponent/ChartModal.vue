<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="query_stats" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal size="large" class="custom-modal" v-model="selectedDataEdit" ok-text="" cancel-text=""
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">
        Parametrning o'zgarish grafigi
      </h3>
      <div>
        <VaDateInput v-model="value" :readonly="false" :format-date="formatDate" :parse-date="parseDate" />
      </div>
      <div>
        <div class="flex gap-5 flex-wrap flex-col">
          <Line :data="chartData" :options="chartOptions" />
        </div>
      </div>
    </VaModal>
  </main>
</template>
<script setup>
import { ref, reactive, inject,watch  } from 'vue';
import axios from 'axios';
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
} from 'chart.js'

// Register necessary components
ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
)

const chartData = reactive({
  labels: ['1', '2', '3', '4'],
  datasets: [{
    label: 'Data One',
    backgroundColor: 'rgba(75, 192, 192, 0.2)',
    borderColor: 'rgba(75, 192, 192, 1)',
    data: [40, 30, 12, 30],
    fill: true,
    tension: 0.1
  }]
})

const chartOptions = reactive({
  responsive: true,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    title: {
      display: true,
      text: 'Line Chart Example'
    }
  },
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Months'
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'Values'
      }
    }
  }
})
const datePlusDay = (date, days) => {
  const d = new Date(date);
  d.setDate(d.getDate() + days);
  return d;
};

const nextWeek = datePlusDay(new Date(), 7);
const value = ref({ start: new Date(), end: nextWeek });
const formatDate = (date) => {
  return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
};

const parseDate = (text) => {
  const [day, month, year] = text.split("/");
  return new Date(year, month - 1, day);
};
const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const result = reactive({
  Name: "",
  ShortName: "",
  Comment: "",
  id: props.params.data['ParametersID']
});

axios.get(`units/${props.params.data['id']}`).then((res) => {
  result.Name = res.data.Name
  result.ShortName = res.data.ShortName
  result.Comment = res.data.Comment
})

// Watch the value object for changes
watch(value, async (newValue) => {
  console.log('Start Date:', newValue.start);
  console.log('End Date:', newValue.end);
  
  try {
    const response = await axios.post('/treeChart', {
      id: props.params.data['ParametersID'],
      start: newValue.start,
      end: newValue.end
    });
    console.log('API Response:', response.data);
  } catch (error) {
    console.error('Error sending data to API:', error);
  }
}, { deep: true });


</script>