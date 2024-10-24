<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="functions" preset="primary" class="mt-1" @click="selectedDataEdit = true, fetchGraphics" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('modals.addFormula') }}
      </h3>
      <div class="flex justify-between">
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
          <div class="flex justify-between">
            <div class="calculator w-1/2">
              <div class="answer">{{ answer }}</div>
              <div class="display">{{ logList + current }}</div>
              <div @click="clear" id="clear" class="btn operator">C</div>
              <div @click="sign" id="sign" class="btn operator">+/-</div>
              <div @click="percent" id="percent" class="btn operator">
                %
              </div>
              <div @click="divide" id="divide" class="btn operator">
                /
              </div>
              <div @click="append('7')" id="n7" class="btn">7</div>
              <div @click="append('8')" id="n8" class="btn">8</div>
              <div @click="append('9')" id="n9" class="btn">9</div>
              <div @click="times" id="times" class="btn operator">*</div>
              <div @click="append('4')" id="n4" class="btn">4</div>
              <div @click="append('5')" id="n5" class="btn">5</div>
              <div @click="append('6')" id="n6" class="btn">6</div>
              <div @click="minus" id="minus" class="btn operator">-</div>
              <div @click="append('1')" id="n1" class="btn">1</div>
              <div @click="append('2')" id="n2" class="btn">2</div>
              <div @click="append('3')" id="n3" class="btn">3</div>
              <div @click="plus" id="plus" class="btn operator">+</div>
              
              <div @click="append('0')" id="n0" class="zero">0</div>
              <div @click="dot" id="dot" class="btn">.</div>
              <div @click="equal" id="equal" class="btn operator">=</div>
              <div @click="append('(')" id="leftBracket" class="btn p-4">(</div>
              <div @click="append(')')" id="rightBracket" class="btn p-4">)</div>

            </div>
            <div v-if="parameters.length" class="ml-2 w-1/2">
              <div class="flex justify-between gap-1">
                <VaButton v-for="(parameter, index) in parameters" :key="parameter.id" color="#f4faff"
                  :style="{ borderRadius: '0' }" @click="append(parameter)">
                  {{ parameter.Name }}
                </VaButton>
              </div>
            </div>
            <div v-else>
              Loading parameters...
            </div>

          </div>


          <VaTextarea class="w-full" v-model="result.Comment" :max-length="125" :label="t('form.comment')" />
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
import anime from 'animejs/lib/anime.es.js';
const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const factoryOptions = ref([]);

const getButton = ref(null);
const parameters = ref([]);
const displayCalculate = ref("");
const logList = ref('');
const current = ref('');
const answer = ref('');
const operatorClicked = ref(true);
const { t, locale } = useI18n();

const result = reactive({
  Calculate: [],
  Comment: "",
  id: props.params.data['ParametersID'],
});
const append = (input) => {

  if (operatorClicked.value) {
    current.value = '';
    operatorClicked.value = false;
  }

  // Check if input is a number (string or number)
  if (typeof input === 'number' || typeof input === 'string') {
    animateNumber(`n${input}`);
    result.Calculate.push(input); // Push the number to the array
    current.value += input; // Display the number on the screen
  }
  
  // Check if input is a parameter (object with 'Name' property)
  else if (typeof input === 'object' && input !== null && input.Name) {
    animateNumber(`n${input.id}`);
    result.Calculate.push(input.id); // Push the parameter ID to the array
    current.value += input.Name; // Display the parameter's Name on the screen
  }
};







const addtoLog = (operator) => {
  if (!operatorClicked.value) {
    logList.value += `${current.value} ${operator} `;
    current.value = '';
    operatorClicked.value = true;

    // Push operator to result.Calculate array
    result.Calculate.push(operator); // Append the operator
  }
};

const animateNumber = (number) => {
  let tl = anime.timeline({
    targets: `#${number}`,
    duration: 250,
    easing: 'easeInOutCubic',
  });
  tl.add({ backgroundColor: '#c1e3ff' });
  tl.add({ backgroundColor: '#f4faff' });
};

const animateOperator = (operator) => {
  let tl = anime.timeline({
    targets: `#${operator}`,
    duration: 250,
    easing: 'easeInOutCubic',
  });
  tl.add({ backgroundColor: '#a6daff' });
  tl.add({ backgroundColor: '#d9efff' });
};

const clear = () => {
  animateOperator('clear');
  current.value = '';
  answer.value = '';
  logList.value = '';
  operatorClicked.value = false;
};

const sign = () => {
  animateOperator('sign');
  if (current.value !== '') {
    current.value = current.value.charAt(0) === '-' ? current.value.slice(1) : `-${current.value}`;
  }
};

const percent = () => {
  animateOperator('percent');
  if (current.value !== '') {
    current.value = `${parseFloat(current.value) / 100}`;
  }
};

const dot = () => {
  animateNumber('dot');
  if (!current.value.includes('.')) {
    append('.');
  }
};

const plus = () => {
  animateOperator('plus');
  addtoLog('+'); // This already appends the operator
};

const minus = () => {
  animateOperator('minus');
  addtoLog('-'); // This already appends the operator
};

const times = () => {
  animateOperator('times');
  addtoLog('*'); // This already appends the operator
};

const divide = () => {
  animateOperator('divide');
  addtoLog('/'); // This already appends the operator
};
// const calculateString = computed(() => result.Calculate.join('&'));
const equal = () => {
  animateOperator('equal');
  if (!operatorClicked.value) {
    answer.value = eval(logList.value + current.value);
  } else {
    answer.value = 'WHAT?!!';
  }
};

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/structure');
    factoryOptions.value = responseGraphics.data.map(factory => ({
      value: factory.id,
      text: locale.value === 'uz' ? factory.Name : factory.NameRus
    }));

    const response = await axios.get(`getForFormule/${props.params.data['id']}`);

    const formulas = response.data;
    const allParameters = formulas.map(entry => entry.parameters);
    getButton.value = response.data.formula; // Contains the formula details
    parameters.value = allParameters.flat();// Contains the associated parameters


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
      result.Calculate = '',
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

<style>
.calculator {
  display: grid;
  grid-template-rows: repeat(7, minmax(60px, auto));
  grid-template-columns: repeat(4, 60px);
  grid-gap: 12px;
  padding: 35px;
  font-family: "Poppins";
  font-weight: 300;
  font-size: 18px;
  background-color: #ffffff;
  border-radius: 10px;
  box-shadow: 0px 3px 50px -30px rgb(79, 98, 112);
}

.btn,
.zero {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  outline: none;
  color: #484848;
  background-color: #f4faff;
  border-radius: 5px;
}

.display,
.answer {
  grid-column: 1 / 5;
  display: flex;
  align-items: center;
}

.display {
  color: #a3a3a3;
  border-bottom: 1px solid #e1e1e1;
  margin-bottom: 15px;
  overflow: hidden;
  text-overflow: clip;
}

.answer {
  font-weight: 500;
  color: #146080;
  font-size: 55px;
  height: 65px;
}

.zero {
  grid-column: 1 / 3;
}

.operator {
  background-color: #d9efff;
  color: #3fa9fc;
}
</style>