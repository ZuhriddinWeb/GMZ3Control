<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="calculate" preset="primary" class="mt-1" @click="selectedDataEdit = true, fetchGraphics" />
    <VaModal size="large" v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')"
      @ok="onSubmit" @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('modals.addFormula') }}
      </h3>
      <div class="">
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
          <div class="flex justify-between">
            <div class="calculator ">
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
            <div class="parameter-list-container h-full">
              <div v-if="staticParameters.length" class="ml-2 mt-2">
                <VaButton v-for="param in staticParameters" :key="param.id" text-color="black" color="warning" border-color="black"
                  :style="{ borderRadius: '0' }" class="mr-2 mb-2" @click="appendStatic(param)">
                  {{ param.Name }}
                </VaButton>
              </div>
              <div v-if="parameters.length" class="ml-2">
                <VaButton
                  v-for="(parameter, index) in parameters.filter(param => param !== null && param !== undefined)"
                  :key="parameter.id" preset="primary" class="mr-2 mb-2" border-color="primary" round
                  :style="{ borderRadius: '0' }" @click="append(parameter)">
                  {{ parameter.Name }}
                </VaButton>
              </div>
              <div v-else>
                Loading parameters...
              </div>
            </div>



          </div>
          <div v-if="timesG.length" class="mt-2">
            <!-- <div class="flex justify-between"> -->
            <VaButton preset="primary" class="mr-2 mb-2" border-color="primary" round v-for="(time, index) in timesG"
              :key="time.id" :style="{ borderRadius: '0' }" @click="append(time)">
              {{ time.Name }}
            </VaButton>

            <!-- </div> -->
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
const staticParameters = ref([]);
const getButton = ref(null);
const parameters = ref([]);
const timesG = ref([]);

const displayCalculate = ref("");
const logList = ref('');
const current = ref('');
const answer = ref('');
const operatorClicked = ref(true);
const { t, locale } = useI18n();

const result = reactive({

  Calculate: [],
  Comment: "",
  id: props.params.data['GparamID'],
  TimeID: props.params.data['id'],

});
const append = (input) => {
  console.log("Input received:", input);

  if (operatorClicked.value) {
    current.value = ''; // Yangi qiymatni kiritishdan oldin o'chirish
    operatorClicked.value = false;
  }

  // Agar input 'GName' bo'lsa (va bu yangi 'Tid')
  if (typeof input === 'object' && input !== null && input.GName) {
    animateNumber(`n${input.id}`);

    // Oxirgi element 'Tid' massiv bo'lishini tekshirish
    const lastElement = result.Calculate[result.Calculate.length - 1];
    if (Array.isArray(lastElement) && lastElement[0].startsWith('Tid=')) {
      // Agar oxirgi element 'Tid' massiv bo'lsa, yangi 'Tid' ni qo'shing
      lastElement.push(`Tid=${input.id}`);
    } else {
      // Aks holda, yangi 'Tid' massivini boshlang
      result.Calculate.push([`Tid=${input.id}`]);
    }

    // Ekranda ko'rsatish uchun '[GName]' ni qo'shing
    current.value += `[${input.Name}]`;
  }
  // Agar input string yoki raqam bo'lsa
  else if (typeof input === 'string' || typeof input === 'number') {
    animateNumber(`n${input}`);
    result.Calculate.push(input); // Oddiy qiymatni qo'shing
    current.value += input;
  }
  // Agar input parametr bo'lsa ('Name' mavjud)
  else if (typeof input === 'object' && input !== null && input.Name) {
    animateNumber(`n${input.id}`);
    result.Calculate.push(`Pid=${input.id}`); // Parametr ID'sini qo'shing
    current.value += `[${input.Name}]`;
  } else {
    console.warn("Unhandled input type:", input);
  }

  console.log("Updated Calculate:", result.Calculate);
};
const appendStatic = (param) => {
  result.Calculate.push(`Static=${param.id}`);
  current.value += `[${param.Name}]`;
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
  result.Calculate = [];
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

  let formulaString = '';

  for (const item of result.Calculate) {
    if (typeof item === 'string') {
      if (item.startsWith('Pid=')) {
        // Bu yer dynamic param value olish uchun api kerak
        formulaString += 10; // placeholder qiymat
      } 
      else if (item.startsWith('Static=')) {
        const staticValue = staticParameters.value.find(s => s.id === item.split('=')[1])?.Value ?? 0;
        formulaString += staticValue;
      } 
      else {
        formulaString += item;
      }
    } else {
      formulaString += item;
    }
  }

  try {
    answer.value = eval(formulaString);
  } catch (error) {
    answer.value = 'Error!';
  }
};

const fetchStaticParameters = async () => {
  try {
    const response = await axios.get('/static'); // Laravel API route
    staticParameters.value = response.data;
  } catch (error) {
    console.error('Error fetching static parameters:', error);
  }
};
const fetchGraphics = async () => {
  try {
    await fetchStaticParameters();
    const responseGraphics = await axios.get('/structure');
    factoryOptions.value = responseGraphics.data.map(factory => ({
      value: factory.id,
      text: locale.value === 'uz' ? factory.Name : factory.NameRus
    }));

    const response = await axios.get(`getForFormule/${props.params.data['GPid']}`);
    const formulas = response.data;

    const sortedData = formulas
      .filter(entry => entry.parameters !== null && entry.parameters !== undefined)
      .sort((a, b) => {
        const orderA = a.formula.OrderNumber ?? Infinity;
        const orderB = b.formula.OrderNumber ?? Infinity;
        return orderA - orderB;
      });

    parameters.value = sortedData
      .map(entry => entry.parameters)
      .filter(param => param !== null && param !== undefined);

    console.log("Tartiblangan parametrlari:", parameters.value);
    const responseTimes = await axios.get(`getForFormuleTimes/${props.params.data['GraphicsID']}`);
    timesG.value = responseTimes.data.map(time => ({
      ...time,
      StartTime: time.StartTime ? time.StartTime.substring(0, 5) : null,
      EndTime: time.EndTime ? time.EndTime.substring(0, 5) : null,
      Name: time.Name ? time.Name.substring(0, 5) : null,

    }));

    // console.log(timesG.value);

    // console.log(responseTimes);

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
      init({ message: t('login.successMessage'), color: 'success' });
      result.Calculate = '';
      result.Comment = '';
      // await fetchData();
      selectedDataEdit.value = false;
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
// watch(selectedDataEdit, (newVal) => {
//   if (newVal) fetchData();
// });
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
  /* border-radius: 10px; */
  box-shadow: 0px 3px 50px -30px rgb(79, 98, 112);
  border: 1px solid rgb(21, 78, 193);
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
  /* border-radius: 5px; */
}

.display,
.answer {
  grid-column: 1 / 5;
  display: flex;
  align-items: center;
}

.display {
  color: #a3a3a3;
  border-bottom: 1px solid rgb(21, 78, 193);
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

.parameter-list-container {
  overflow-y: auto;
  max-height: 550px;
  /* Max balandlikni mos ravishda o'zgartiring */
}
</style>