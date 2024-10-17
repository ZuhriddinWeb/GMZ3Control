<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchParams">
        {{ t('modals.editFactory') }} {{ props.params.data['id'] }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.ParametersID" value-by="value" class="mb-1" :label="t('menu.params')"
              :options="paramsOptions" searchable />
            <VaSelect v-model="result.GrapicsID" value-by="value" class="mb-1" :label="t('menu.graphictimes')"
              :options="graphicOptions" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.FactoryStructureID" value-by="value" class="mb-1" :label="t('menu.structure')"
              :options="structureOptions" clearable />
              <VaSelect v-model="result.BlogID" value-by="value" class="mb-1" :label="t('menu.blogs')"
              :options="blogsOptions ?? []" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-1 gap-1 items-end w-full">
            <VaSelect v-model="result.SourceID" value-by="value" class="mb-1" :label="t('menu.sources')"
              :options="sourcesOptions" clearable />
          </div>
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.CurrentTime" stateful highlight-weekend />
            <VaDatePicker v-model="result.EndingTime" stateful highlight-weekend  />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-1 gap-1 items-end w-full">
            <VaSelect v-model="result.PageId" value-by="value" class="mb-1" :label="t('menu.pages')"
              :options="pagesOptions" clearable /></div>
          <VaInput type="number" class="w-full" v-model="result.OrderNumber"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            :label="t('modals.ordernumber')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
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
const pagesOptions =ref([]);
const result = reactive({
  ParametersID: "",
  FactoryStructureID: "",
  GrapicsID: "",
  SourceID: "",
  CurrentTime: "",
  EndingTime: "",
  OrderNumber: "",
  BlogID: "",
  PageId:"",
  id: props.params.data['id']
});

const fetchParams = async () => {
  
  try {
    const [resParam, resGraphic, resStruct, resBlogs, resSources,responsePages, response] = await Promise.all([
      axios.get('/param'),
      axios.get('/graphics'),
      axios.get('/structure'),
      axios.get('/blogs'),
      axios.get('/sources'),
      axios.get('/pages'),
      axios.get(`get-params-for-id-edit/${props.params.data['id']}`)
    ]);

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
    pagesOptions.value = responsePages.data.map(source => ({
      value: source.id,
      text: source.Name
    }));
    
    result.ParametersID = response.data[0].Pid;
    result.GrapicsID = +response.data[0].GrapicsID;
    result.FactoryStructureID = +response.data[0].Sid;
    // result.BlogID = +response.data[0].BlogsID;
    result.SourceID = +response.data[0].SourceID;
    // result.PageId = +response.data[0].PageId;
    result.OrderNumber = response.data[0].OrderNumber;

    result.CurrentTime = parseISO(response.data[0].CurrentTime);
    result.EndingTime = parseISO(response.data[0].EndingTime);

  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

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
