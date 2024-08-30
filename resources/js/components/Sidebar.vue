<template>
  <VaLayout :top="{ fixed: true, order: 1 }" :left="{
    fixed: true,
    absolute: breakpoints.smDown,
    order: 2,
    overlay: breakpoints.smDown && isSidebarVisible,
    class: 'custom-sidebar'
  }" @left-overlay-click="isSidebarVisible = false">
    <template #top>
      <VaNavbar>
        <template #left>
          <VaButton preset="secondary" :icon="isSidebarVisible ? 'menu_open' : 'menu'"
            @click="isSidebarVisible = !isSidebarVisible" />
        </template>
        <template #center>
          <div v-if="store.state.user && store.state.user.name" class="flex justify-between font-semibold">
            <div class="flex justify-between items-center">
              <span class="material-icons user-icon text-blue-800">account_circle</span>
              <p class="ml-1">{{ t('table.user') }}: {{ store.state.user.name }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-blue-800">manage_history</span>
              <p class="ml-1">{{ t('table.change') }}: {{ displayValue }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-blue-800">diamond</span>
              <p class="ml-1">{{ t('table.parameters') }}: {{ paramCount }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-green-500">diamond</span>
              <p class="ml-1">{{ t('table.input') }}: {{ store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-orange-500">diamond</span>
              <p class="ml-1">{{ t('table.output') }}: {{ paramCount - store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-cyan-800">timelapse</span>
              <p class="ml-1">{{ t('menu.timer') }}: {{ currentTime }} </p>
            </div>
          </div>
        </template>
        <template #right>
          <div class="flex items-center ml-3 mr-14">
            <VaButton preset="secondary" @click="handleLogout">
              <VaIcon name="logout" style="margin-right: 0.5rem;" />
              {{ t('menu.logout') }}
            </VaButton>
          </div>
        </template>
      </VaNavbar>
      <VaDivider style="margin: 0" />
    </template>
    <template #left>
      <VaSidebar v-model="isSidebarVisible" class="custom-sidebar">
        <!-- Language Change Button -->
        <VaButton @click="changeLanguage" style="margin-bottom: 1rem; border-radius: 0; padding: 0.6rem;">
          <VaIcon name="language" style="margin-right: 0.5rem;" />
          {{ currentLanguageLabel }}
        </VaButton>
        <!-- User Menu Items -->
        <template v-for="menuItem in generateUserMenu" :key="menuItem.title">
          <MenuItem :menuItem="menuItem" />
        </template>
      </VaSidebar>
    </template>
    <template #content>
      <main>
        <article>
          <slot />
        </article>
      </main>
    </template>
  </VaLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useBreakpoint } from 'vuestic-ui';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import MenuItem from './MenuItem.vue'; // Import the MenuItem component

const breakpoints = useBreakpoint();
const store = useStore();
const router = useRouter();
const { t, locale } = useI18n();
const paramCount = ref(0);
const currentTime = ref('');
const isSidebarVisible = ref(false); // Initialize as false

watch(() => breakpoints.smUp, (newValue) => {
  if (newValue) {
    isSidebarVisible.value = store.state.user ? false : true; // Hide if user is logged in
  } else {
    isSidebarVisible.value = newValue;
  }
});

const currentHour = new Date().getHours();
const displayValue = computed(() => (currentHour >= 8 && currentHour < 20) ? 1 : 2);

const updateCurrentTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString();
};

const fetchParameterCount = async () => {
  try {
    const change = (currentHour >= 8 && currentHour < 20) ? 1 : 2;
    const response = await axios.get(`/get-params-for-user-count/${store.state.user.structure_id}/${change}`);
    paramCount.value = response.data;
  } catch (error) {
    console.error('Error fetching parameters count:', error);
  }
};

const menu = ref([
  { title: 'menu.home', icon: 'home', path: '/' },
  {
    title: 'menu.lists', icon: 'format_list_bulleted', children: [
      { title: 'menu.structure', icon: 'dashboard', path: '/structure' },
      { title: 'menu.blogs', icon: 'account_tree', path: '/blogs' },
      { title: 'menu.units', icon: 'ad_units', path: '/units' },
      { title: 'menu.graphics', icon: 'schedule', path: '/graphics' },
      { title: 'menu.graphictimes', icon: 'alarm', path: '/graphictimes' },
      { title: 'menu.params', icon: 'format_list_bulleted', path: '/params' },
      { title: 'menu.paramgraphics', icon: 'schema', path: '/paramgraphics' },
    ]
  },
  { title: 'menu.vparams', icon: 'diamond', path: '/vparams' },
  { title: 'menu.users', icon: 'person', path: '/users' },
]);

const handleLogout = async () => {
  try {
    await store.dispatch('logout');
    router.push({ name: 'login' });
    isSidebarVisible.value = false; // Hide sidebar on logout
  } catch (error) {
    console.error('Error during logout:', error);
  }
};

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  localStorage.setItem('locale', locale.value);
};

const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
});

const generateUserMenu = computed(() => {
  const user = store.state.user;
  if (!user) return [];
  const roles = user?.roles?.filter(role => role.pivot.view === "1");
  return menu.value?.filter(item => {
    const role = roles.filter(role => item.title == role.name);
    return role.length;
  });
});

onMounted(() => {
  fetchParameterCount();
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  updateCurrentTime();
  setInterval(updateCurrentTime, 1000);

  // Check if user is logged in and set sidebar visibility
  if (store.state.user) {
    isSidebarVisible.value = false;
  }
  
});
</script>


<style>
.custom-sidebar {
  background-color: #57534e;
}

.hover-item:hover {
  background-color: #154EC1;
  color: white;
}

.hover-logout:hover {
  background-color: rgb(220 38 38);
  color: white;
}
</style>
