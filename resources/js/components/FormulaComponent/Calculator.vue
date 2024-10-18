<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="functions" preset="primary" class="mt-1" @click="selectedDataEdit = true, fetchGraphics" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('modals.addFormula') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
          <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
            <VaTextarea 
              class="w-full" 
              v-model="displayCalculate" 
              :max-length="125" 
              readonly
            />
          </div>
         
          <div class="grid grid-cols-9 gap-1 mt-3 w-full">
            <VaButton color="success" @click="appendToCalculate('&')" :style="{ borderRadius: '0' }">&</VaButton>
            <VaButton color="info" @click="appendToCalculate('(')" :style="{ borderRadius: '0' }">(</VaButton>
            <VaButton color="info" @click="appendToCalculate(')')" :style="{ borderRadius: '0' }">)</VaButton>
            <VaButton color="info" @click="appendToCalculate('+')" :style="{ borderRadius: '0' }">+</VaButton>
            <VaButton color="info" @click="appendToCalculate('-')" :style="{ borderRadius: '0' }">-</VaButton>
            <VaButton color="info" @click="appendToCalculate('*')" :style="{ borderRadius: '0' }">×</VaButton>
            <VaButton color="info" @click="appendToCalculate('/')" :style="{ borderRadius: '0' }">÷</VaButton>
            <VaButton color="info" @click="appendToCalculate('%')" :style="{ borderRadius: '0' }">%</VaButton>
            <VaButton color="info" @click="removeLastCharacter" :style="{ borderRadius: '0' }"><=</VaButton>

          </div>
          
          <div class="grid grid-cols-11 gap-1 mt-3 w-full">
            <VaButton color="secondary" @click="appendToCalculate('1')" :style="{ borderRadius: '0' }">1</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('2')" :style="{ borderRadius: '0' }">2</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('3')" :style="{ borderRadius: '0' }">3</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('4')" :style="{ borderRadius: '0' }">4</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('5')" :style="{ borderRadius: '0' }">5</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('6')" :style="{ borderRadius: '0' }">6</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('7')" :style="{ borderRadius: '0' }">7</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('8')" :style="{ borderRadius: '0' }">8</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('9')" :style="{ borderRadius: '0' }">9</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('0')" :style="{ borderRadius: '0' }">0</VaButton>
            <VaButton color="secondary" @click="appendToCalculate('.')" :style="{ borderRadius: '0' }">.</VaButton>
          </div>
          
          <div class="flex justify-between gap-1 mt-4">
            <VaButton 
              v-for="(parameter, index) in parameters" 
              :key="parameter.id" 
              color="primary"
              :style="{ borderRadius: '0' }"
              @click="appendParameter(parameter)" 
            >
              {{ parameter.Name }} 
            </VaButton>
          </div>
          
          <VaTextarea 
            class="w-full" 
            v-model="result.Comment" 
            :max-length="125" 
            :label="t('form.comment')" 
          />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const factoryOptions = ref([]);

const getButton = ref(null);
const parameters = ref(null);
const displayCalculate = ref("");

const { t, locale } = useI18n();

const result = reactive({
  Calculate:"",
  Comment: "",
  id: props.params.data['id'],
});
// New property to show names in the textarea

// Function to append general values like +, -, numbers
const appendToCalculate = (value) => {
  result.Calculate += value; // Append the value with a comma for storage
  updateDisplayCalculate(); // Update the display text
};

// Function to append parameter by ID
const appendParameter = (parameter) => {
  result.Calculate += parameter.id ; // Append the parameter ID
  displayCalculate.value += parameter.Name + ','; // Append the parameter Name for display
};

// Function to remove the last character from Calculate
const removeLastCharacter = () => {
  result.Calculate = result.Calculate.slice(0, -1); // Remove the last character
  updateDisplayCalculate(); // Update the display text
};

// Function to update displayCalculate based on Calculate
const updateDisplayCalculate = () => {
  // Convert Calculate string to an array, split by comma, filter out empty values
  const ids = result.Calculate.split(',').filter(Boolean);
  displayCalculate.value = ids.map(id => {
    const parameter = parameters.value.find(param => param.id === id); // Ensure we use .value for reactivity
    return parameter ? parameter.Name : id; // Display the name if found, otherwise show the ID
  }).join(', ');
};

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/structure');
    factoryOptions.value = responseGraphics.data.map(factory => ({
      value: factory.id,
      text: locale.value === 'uz' ? factory.Name : factory.NameRus
    }));

    const response = await axios.get(`formula/${props.params.data['id']}`);
    getButton.value = response.data.formula; // Contains the formula details
    parameters.value = response.data.parameters; // Contains the associated parameters


    // result.StructureID = +response.data.StructureID;
    // result.Name = response.data.Name;
    // result.ShortName = response.data.ShortName;
    // result.Comment = response.data.Comment;
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};


const onSubmit = async () => {
  try {
    const { data } = await axios.post("/calculator", result);
    if (data.status === 200) {
      result.Calculate='',
      result.ParametersId = '';
      result.Comment = '';
      await fetchData();
      init({ message: t('login.successMessage'), color: 'success' });
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

watch(() => result.StructureID, (newVal) => {
  // console.log('Selected StructureID:', newVal);
});
</script>
