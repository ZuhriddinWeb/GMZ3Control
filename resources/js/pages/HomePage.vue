<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <div class="flex w-full bg-gray-200 items-center h-full">
      <main class="bg-white h-[90%] mx-4">
        <div class="flex justify-between m-8">
          <div class="flex justify-between">
            <img src="../../../public/user.png" alt="" class="w-[10px] h-[250px] block">
            <div class="flex flex-col justify-start">
              <div>
                <span class="font-bold text-xl">{{ store.state.user.name }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
              <div>
                <span class="font-bold text-xl">{{ store.state.user.phone }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
              <div>
                <span class="font-bold text-xl">{{ store.state.user.login }}</span>
                <span class="material-icons verified-icon">verified</span>
              </div>
            </div>
          </div>
          <div class="flex flex-col h-6">
            <div class="flex justify-between">
              <div @click="showModal = true"
                class="bg-green-200 text-green-600 borde p-3 text-center rounded-lg items-center mr-1 flex justify-between cursor-pointer hover:bg-green-300">
                <span>{{ t('menu.edit') }}</span>
              </div>
              <div @click.prevent="handleLogout"
                class="bg-red-200 text-red-600 borde p-3 rounded-lg text-center items-center ml-1 flex justify-between cursor-pointer hover:bg-red-300">
                <span>{{ t('menu.logout') }}</span>
              </div>
            </div>
          </div>
        </div>
        <!-- <div class="h-1/3">
          <va-stepper v-model="step" :steps="steps" finishButtonHidden="false" controlsHidden="false"
            navigationDisabled="false"></va-stepper>
          <Pie id="my-chart-id" :options="chartOptions" :data="chartData" />
        </div> -->
      </main>
    </div>

    <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit"
      close-button>
      <h3 class="va-h3" @vue:mounted="fetchUser">{{ t('modals.editFactory') }}</h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <VaInput class="w-full" v-model="result.Name"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.userName')" />
          <VaInput class="w-full" v-model="result.Phone"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" type="tel"
            :label="t('form.userPhone')" placeholder="597****" />
          <VaInput class="w-full" v-model="result.Login"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.login')" />
          <VaInput class="w-full" v-model="result.Password"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.password')" />
        </VaForm>
      </div>
    </VaModal>
  </div>
</template>


<script setup>
import { ref, reactive ,onMounted} from 'vue';
import { useStore } from 'vuex';
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const store = useStore();
const router = useRouter();
const { t } = useI18n();

const showModal = ref(false);

const result = reactive({
  Name: "",
  Phone: "",
  Login: "",
  Password: "",
});

const fetchUser = async () => {
  try {
    const [response] = await Promise.all([
      axios.get(`users/${store.state.user.id}`)
    ]);
    result.Name = response.data.name;
    result.Login = response.data.login;
    result.Phone = response.data.phone;
    result.Password = response.data.password;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const onSubmit = async () => {
  try {
    const { data } = await axios.put(`/users/${store.state.user.id}`, result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.Phone = '';
      result.Login = '';
      result.Password = '';
      init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

const handleLogout = async () => {
  try {
    await store.dispatch('logout');
    router.push({ name: 'login' });
  } catch (error) {
    console.error('Error during logout:', error);
  }
};

ChartJS.register(ArcElement, Tooltip, Legend);

const step = ref(0);
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

const chartOptions = reactive({
  responsive: true,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    tooltip: {
      callbacks: {
        label: function (tooltipItem) {
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
  { label: 'Checkout', icon: 'local_shipping' },
  { label: 'Review order', icon: 'done_all' },
  { label: 'Confirm and pay', icon: 'payments' },
];
onMounted(() => {
  router.push('/vparams');
});
</script>


<style scoped>
.verified-icon {
  font-size: 24px;
  color: #0F9D58;
  margin-left: 8px;
}
</style>
