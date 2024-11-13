<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true, fetchGraphics" />
    <VaModal size="large" v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('modals.editFactory') }}
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
// const append = async (input) => {
//   if (operatorClicked.value) {
//     current.value = '';
//     operatorClicked.value = false;
//   }

//   if (typeof input === 'object' && input !== null && input.GName) {
//     // Agar input vaqt bo'lsa
//     animateNumber(`n${input.id}`);
//     const timeName = await fetchTimeById(input.id);
//     result.Calculate.push(`[${timeName}]`); // "[09:00]" formatida qo'shish
//     current.value += `[${timeName}]`;
//   } else if (typeof input === 'object' && input !== null && input.Name) {
//     // Agar input parametr bo'lsa
//     animateNumber(`n${input.id}`);
//     const paramName = await fetchParameterById(input.id);
//     result.Calculate.push(`[${paramName}]`); // "[KPS Zichligi]" formatida qo'shish
//     current.value += `[${paramName}]`;
//   } else if (typeof input === 'string') {
//     // Agar input oddiy raqam yoki operator bo'lsa
//     animateNumber(`n${input}`);
//     result.Calculate.push(input);
//     current.value += input;
//   }
// };
const append = (input) => {
  console.log(input);
  
  if (operatorClicked.value) {
    current.value = '';
    operatorClicked.value = false;
  }
 // Check if input is a time object (with GName property)
 if (typeof input === 'object' && input !== null && input.GName) {
    animateNumber(`n${input.id}`);
    result.Calculate.push(`Tid=${input.id}`); // Push "Tid=id" to the array
    current.value += `[${input.Name}]`; // Display it as "[Tid=id]"
  } 
  // Check if input is a number (string or number)
  else if (typeof input === 'string') {
    animateNumber(`n${input}`);
    result.Calculate.push(input); // Push the number to the array
    current.value += input; // Display the number on the screen
  }

  // Check if input is a parameter (object with 'Name' property)
  else if (typeof input === 'object' && input !== null && input.Name) {
    animateNumber(`n${input.id}`);
    result.Calculate.push(`Pid=${input.id}`);
    current.value += `[${input.Name}]`; // Wrap the parameter's Name in brackets and display it
  }
  else if (typeof input === 'object' && input !== null && input.GName) {
    animateNumber(`n${input.id}`);
    result.Calculate.push(`Tid=${input.id}`);
    current.value += `[${input.GName}]`; // Wrap the GName in brackets and display it
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
  if (!operatorClicked.value) {
    answer.value = eval(logList.value + current.value);
  } else {
    answer.value = 'WHAT?!!';
  }
};
const fetchParameterById = async (id) => {
  try {
    const response = await axios.get(`/param/${id}`);
    return response.data.Name;
  } catch (error) {
    console.error('Parametrni olishda xato:', error);
    return `Pid=${id}`;
  }
};

const fetchTimeById = async (id) => {
  try {
    const response = await axios.get(`/time/${id}`);
    const timeName = response.data.Name;
    // Vaqtni HH:MM formatiga qisqartirish
    return timeName ? timeName.substring(0, 5) : `[Tid=${Name}]`;
  } catch (error) {
    console.error('Vaqtni olishda xato:', error);
    return `Tid=${id}`;
  }
};

const fetchGraphics = async () => {
  try {
    const responseEdit = await axios.get(`getForFormuleId/${props.params.data['GparamID']}/${props.params.data['id']}`);
    console.log(responseEdit);
    
    const calculateData = responseEdit.data.Calculate;
    result.Comment = responseEdit.data.Comment || "";

    // Calculate massivini to'ldirish
    result.Calculate = await Promise.all(calculateData.map(async (item) => {
      if (item.startsWith("Pid=")) {
        const paramId = item.split("=")[1];
        const paramName = await fetchParameterById(paramId);
        return `[${paramName}]`; // Parametrni qavs ichida qo'shish
      } else if (item.startsWith("Tid=")) {
        const timeId = item.split("=")[1];
        const timeName = await fetchTimeById(timeId);
        return `[${timeName}]`; // Vaqtni qavs ichida qo'shish
      }
      return item;
    }));

    current.value = result.Calculate.join(""); // Bo'sh joylarsiz birlashtirish

    const response = await axios.get(`getForFormule/${props.params.data['GPid']}`);

    const formulas = response.data;
    const allParameters = formulas.map(entry => entry.parameters);
    getButton.value = response.data.formula; // Contains the formula details
    parameters.value = allParameters.flat();// Contains the associated parameters

    const responseTimes = await axios.get(`getForFormuleTimes/${props.params.data['GraphicsID']}`);
    timesG.value = responseTimes.data.map(time => ({
      ...time,
      StartTime: time.StartTime ? time.StartTime.substring(0, 5) : null,
      EndTime: time.EndTime ? time.EndTime.substring(0, 5) : null,
      Name: time.Name ? time.Name.substring(0, 5) : null,

    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};


const onSubmit = async () => {
  try {
    const { data } = await axios.put("/calculator", result);
    if (data.status === 200) {
      result.Calculate = '',
      result.ParametersId = '';
      result.Comment = '';
      // await fetchData();
      init({ message: t('login.successMessage'), color: 'success' });
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
</style>