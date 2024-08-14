<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="settings" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal max-width="45%" v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3>
       <span class="va-h3">{{ t('table.user') }} : {{ result.userName }}</span>  
      </h3>
      <div>
        <div class="va-table-responsive">
          <table class="va-table va-table--clickable w-full">
            <thead>
              <tr>
                <th>Nomi</th>
                <th>Ruxsat</th>
                <th>Yaratish</th>
                <th>O'zgartirish</th>
                <th>O'chirish</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="role in roles" :key="role.id">
                <td>{{ t(`${role.name}`) }}</td>
                <td>

                  <VaSwitch :model-value="role.view" @change="() => onChange(role, 'view')"
                    :color="getColor(role.view)" />
                </td>
                <td v-if="role.view">
                  <VaSwitch :model-value="role.create" @change="() => onChange(role, 'create')"
                    :color="getColor(role.create)" />
                </td>
                <td v-if="role.view">
                  <VaSwitch :model-value="role.update" @change="() => onChange(role, 'update')"
                    :color="getColor(role.update)" />
                </td>
                <td v-if="role.view">
                  <VaSwitch :model-value="role.delete" @change="() => onChange(role, 'delete')"
                    :color="getColor(role.delete)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(['params']);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const roles = ref([]);
import { useI18n } from 'vue-i18n';

import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const { locale, t } = useI18n();

const result = reactive({
  id: props.params.data['id'],
  userName:props.params.data['name']
});

const onChange = (role, action) => {
  role[action] = !role[action];
  console.log(`${action} for ${role.name} changed to ${role[action]}`);
};

const getColor = (value) => {
  return value ? 'success' : 'danger';
};


async function getUserRole() {
  axios.get(`/user_role/${props.params.data['id']}`).then((res) => {
    res.data.forEach((user_role) => {
      const selectedRole = roles.value.find((role) => role.id == user_role.role_id)
      if (selectedRole == null) return
      selectedRole.view = user_role.view
      selectedRole.create = user_role.create
      selectedRole.update = user_role.update
      selectedRole.delete = user_role.delete
    })
  })
}


async function fetchData() {
  try {
    const response = await axios.get('/role');
    roles.value = Array.isArray(response.data) ? response.data : response.data.items;
    roles.value.forEach(role => {
      role.id = role.id
      role.view = role.view || false;
      role.create = role.create || false;
      role.update = role.update || false;
      role.delete = role.delete || false;
    });

  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const rolesData = roles.value.map(role => ({
      id: role.id,
      name: role.name,
      view: role.view,
      create: role.create,
      edit: role.update,
      delete: role.delete
    }));

    const { data } = await axios.post('/user_role', {
      ...result,
      roles: rolesData
    });

    if (data.status === 200) {
      // onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

onMounted(async () => {
  await fetchData();
  getUserRole()
});
</script>

<style>
.va-table-responsive {
  overflow-x: auto;
}

.va-table {
  width: 100%;
  border-collapse: collapse;
}

.va-table th,
.va-table td {
  padding: 8px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}
</style>
