<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchParams">
        {{ t('modals.editFactory') }} {{ props.params.data['ParametersID'] }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.ParametersID" value-by="value" class="mb-1" :label="t('menu.params')"
              :options="paramsOptions" searchable />
            <VaSelect v-model="result.GrapicsID" value-by="value" class="mb-1" :label="t('menu.graphictimes')"
              :options="graphicOptions" clearable />
          </div>
          <VaInput class="w-full" v-model="result.ShortName"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            :label="t('form.shortName')" />
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.FactoryStructureID" value-by="value" class="mb-1" :label="t('menu.structure')"
              :options="structureOptions" clearable />
            <VaSelect v-model="result.BlogID" value-by="value" class="mb-1" :label="t('menu.blogs')"
              :options="blogsOptions ?? []" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.SourceID" value-by="value" class="mb-1" :label="t('menu.sources')"
              :options="sourcesOptions" clearable />
            <VaSelect v-model="result.WithFormula" value-by="value" class="mb-1" :label="t('menu.formula')"
              :options="FormulaOptions" clearable />
          </div>
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.CurrentTime" stateful highlight-weekend />
            <VaDatePicker v-model="result.EndingTime" stateful highlight-weekend />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-1 gap-1 items-end w-full">
            <VaSelect v-model="result.PageId" value-by="value" class="mb-1" :label="t('menu.pages')"
              :options="pagesOptions" searchable />
            <VaSelect v-model="result.GroupID" value-by="value" class="mb-1" :label="t('menu.groups')"
              :options="GroupsOptions" searchable />
          </div>
          <VaInput type="number" class="w-full" v-model="result.OrderNumber"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            :label="t('modals.ordernumber')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted,watch } from 'vue';
import { useI18n } from 'vue-i18n'; // Import useI18n from vue-i18n
import axios from 'axios';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
import { parseISO } from 'date-fns';
const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const { init } = useToast();
const { t } = useI18n(); // Use i18n


const paramsOptions = ref([]);
const graphicOptions = ref([]);
const structureOptions = ref([]);
const blogsOptions = ref([]);
const sourcesOptions = ref([]);
const pagesOptions = ref([]);
const GroupsOptions = ref([]);
const FormulaOptions = ref([]);
const paramsRawData = ref([]);
const result = reactive({
  ParametersID: "",
  FactoryStructureID: "",
  GrapicsID: "",
  SourceID: "",
  CurrentTime: "",
  EndingTime: "",
  OrderNumber: "",
  ShortName: "",
  BlogID: "",
  PageId: "",
  GroupID: "",
  WithFormula: "",
  id: props.params.data['id']
});

const fetchParams = async () => {

  try {
    const [resParam, resGraphic, resStruct, resBlogs, resSources, responsePages, response, resFormula, resGroups] = await Promise.all([
      axios.get('/param'),
      axios.get('/graphics'),
      axios.get('/structure'),
      axios.get('/blogs'),
      axios.get('/sources'),
      axios.get('/pages'),
      axios.get(`get-params-for-id-edit/${props.params.data['id']}`, { timeout: 10000 }),
      axios.get('/formula'),
      axios.get('/groups'),
    ]);
    paramsRawData.value = resParam.data;
    paramsOptions.value = resParam.data.map(param => ({
      value: param.Uuid,
      text: param.Name
    }));
    graphicOptions.value = resGraphic.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name
    }));
    structureOptions.value = resStruct.data.map(struct => ({
      value: struct.id,
      text: struct.Name
    }));
    blogsOptions.value = resBlogs.data.map(blogs => ({
      value: blogs.id,
      text: blogs.Name
    }));
    sourcesOptions.value = resSources.data.map(source => ({
      value: source.id,
      text: source.Name
    }));
    pagesOptions.value = responsePages.data.map(page => ({
      value: page.NumberPage,
      text: page.Name
    }));

    FormulaOptions.value = resFormula.data.map(formula => ({
      value: formula.id,
      text: formula.Name
    }));

    GroupsOptions.value = resGroups.data.map(group => ({
      value: group.id,
      text: group.Name
    }));
    result.ParametersID = response.data[0]?.ParametersID || null;
    result.GrapicsID = +response.data[0]?.GrapicsID || null;
    result.FactoryStructureID = +response.data[0]?.Sid || null;
    result.SourceID = +response.data[0]?.SourceID || null;
    result.WithFormula = response.data[0]?.WithFormula ?? null;

    result.OrderNumber = response.data[0]?.OrderNumber || null;

    result.CurrentTime = response.data[0]?.CurrentTime ? parseISO(response.data[0].CurrentTime) : null;
    result.EndingTime = response.data[0]?.EndingTime ? parseISO(response.data[0].EndingTime) : null;
    result.BlogID = +response.data[0].BlogsID ?? null;
    result.PageId = response.data[0]?.PageId ?? null;
    result.GroupID = response.data[0]?.GroupID ?? null;
    
    const matchedParam = resParam.data.find(param => param.Uuid === result.ParametersID);
    if (matchedParam) {
      
      result.ShortName = matchedParam.ShortName;
    }

  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
watch(() => result.ParametersID, (newVal) => {
  const matchedParam = paramsRawData.value.find(p => p.Uuid === newVal);
  if (matchedParam) {
    result.ShortName = matchedParam.ShortName || '';
  }
});

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/paramsgraph", result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};
</script>
