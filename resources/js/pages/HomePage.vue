<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <div class="flex w-full bg-gray-200 items-center h-full">
      <main class=" bg-white h-[90%] mx-4">
        <div class="flex justify-between m-8">
          <div class="flex justify-between">
            <img src="../../../public/user.png" alt="" class="w-[10px] h-[250px] block">
            <div class="flex flex-col justify-start">
              <div>
                <span class="font-bold text-xl">{{ $store.state.user.name }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
              <div>
                <span class="font-bold text-xl">{{ $store.state.user.phone }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
              <div>
                <span class="font-bold text-xl">{{ $store.state.user.login }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
            </div>
          </div>
          <div class="flex flex-col h-6">
            <div class="flex justify-between">
              <div
                class="bg-green-200 text-green-600 borde p-3  text-center rounded-lg items-center mr-1 flex justify-between cursor-pointer hover:bg-green-300">
                <span>Tahrirlash</span>
              </div>
              <div
                class="bg-red-200 text-red-600 borde p-3 rounded-lg text-center items-center ml-1 flex justify-between cursor-pointer hover:bg-red-300">
                <span>Chiqish</span>
              </div>
            </div>
            <!-- <div>2</div> -->
          </div>
        </div>
        <div class="h-1/3">
          <va-stepper v-model="step" :steps="steps" finishButtonHidden="false" controlsHidden="false"
            navigationDisabled="false">
          </va-stepper>
          <Pie  id="my-chart-id" :options="chartOptions" :data="chartData" />
        </div>

      </main>
    </div>
  </div>
</template>

<script setup>
import { ref,reactive } from 'vue';
import { useStore } from 'vuex';
import { Pie } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);


const step = ref(0);
// Pie chart data
const chartData = reactive({
  labels: ['Category 1', 'Category 2', 'Category 3', 'Category 4'],
  datasets: [{
    label: 'Data Distribution',
    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
    borderColor: 'rgba(0,0,0,0.1)',
    borderWidth: 1,
    data: [40, 30, 12, 30]
  }]
});

// Pie chart options
const chartOptions = reactive({
  responsive: true,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    tooltip: {
      callbacks: {
        label: function(tooltipItem) {
          return tooltipItem.label + ': ' + tooltipItem.raw + '%';
        }
      }
    },
    title: {
      display: true,
      text: 'Pie Chart Example'
    }
  }
});
const steps = [
  { label: 'Choose your product', icon: 'store' },
  { label: 'Checkout', icon: 'local_shipping', },
  { label: 'Review order', icon: 'done_all' },
  { label: 'Confirm and pay', icon: 'payments' },
];
</script>

<style scoped>
.verified-icon {
  font-size: 24px;
  color: #0F9D58;
  margin-left: 8px;
}
</style>
