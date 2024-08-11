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
              <p class="ml-1">{{ t('table.change') }}:{{ displayValue }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-blue-800">diamond</span>
              <p class="ml-1">{{ t('table.parameters') }}:{{ paramCount }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-green-500">diamond</span>
              <p class="ml-1">{{ t('table.input') }}:{{ store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-orange-500">diamond</span>
              <p class="ml-1">{{ t('table.output') }}:{{ paramCount-store.state.countInputedParams }} /</p>
            </div>
            <div class="flex justify-between items-center ml-3">
              <span class="material-icons user-icon text-cyan-800">timelapse</span>
              <p class="ml-1">{{ t('menu.timer') }}: {{ currentTime }} </p>
            </div>
          </div>
        </template>

      </VaNavbar>
      <VaDivider style="margin: 0" />
    </template>
    <template #left>
      <VaSidebar v-model="isSidebarVisible" class="custom-sidebar">
        <!-- Language Change Button -->
        <VaButton @click="changeLanguage" style="margin-bottom: 1rem; border-radius: 0;">
          <VaIcon name="language" style="margin-right: 0.5rem;" />
          {{ currentLanguageLabel }}
        </VaButton>
        <!-- User Menu Items -->
        <template v-for="menuItem in menu" :key="menuItem.icon">
          <a v-if="menuItem.path === '/logout'" @click.prevent="handleLogout">
            <VaSidebarItem>
              <VaSidebarItemContent>
                <VaIcon :name="menuItem.icon" />
                <VaSidebarItemTitle>
                  {{ t(menuItem.title) }}
                </VaSidebarItemTitle>
              </VaSidebarItemContent>
            </VaSidebarItem>
          </a>
          <router-link v-else :to="menuItem.path" class="router-link">
            <VaSidebarItem>
              <VaSidebarItemContent>
                <VaIcon :name="menuItem.icon" />
                <VaSidebarItemTitle>
                  {{ t(menuItem.title) }}
                </VaSidebarItemTitle>
              </VaSidebarItemContent>
            </VaSidebarItem>
          </router-link>
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

const breakpoints = useBreakpoint();
const store = useStore();
const router = useRouter();
const { t, locale } = useI18n();
const paramCount = ref(0);
const currentTime = ref('');

const isSidebarVisible = ref(breakpoints.smUp);

watch(() => breakpoints.smUp, (newValue) => {
  isSidebarVisible.value = newValue;
});
const currentHour = new Date().getHours();

const displayValue = computed(() => {
  return (currentHour >= 8 && currentHour < 20) ? 1 : 2;
});
const updateCurrentTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString(); // Formats the time as 'HH:MM:SS AM/PM'
};
const fetchParameterCount = async () => {
  try {
    const currentHour = new Date().getHours();
    const change = (currentHour >= 8 && currentHour < 20) ? 1 : 2;
    const response = await axios.get(`/get-params-for-user-count/${store.state.user.structure_id}/${change}`);
    paramCount.value = response.data;
  } catch (error) {
    console.error('Error fetching parameters count:', error);
  }
};
const menu = ref([
  { title: 'menu.dashboard', icon: 'dashboard', path: '/' },
  { title: 'menu.factory', icon: 'factory', path: '/factory' },
  { title: 'menu.structure', icon: 'dashboard', path: '/structure' },
  { title: 'menu.blogs', icon: 'account_tree', path: '/blogs' },
  { title: 'menu.units', icon: 'ad_units', path: '/units' },
  { title: 'menu.graphics', icon: 'schedule', path: '/graphics' },
  { title: 'menu.graphictimes', icon: 'alarm', path: '/graphictimes' },
  { title: 'menu.paramtypes', icon: 'format_list_numbered', path: '/paramtypes' },
  { title: 'menu.params', icon: 'format_list_bulleted', path: '/params' },
  { title: 'menu.sources', icon: 'content_copy', path: '/sources' },
  { title: 'menu.changes', icon: 'manage_history', path: '/changes' },
  { title: 'menu.paramgraphics', icon: 'schema', path: '/paramgraphics' },
  { title: 'menu.vparams', icon: 'diamond', path: '/vparams' },
  { title: 'menu.users', icon: 'person', path: '/users' },
  { title: 'menu.logout', icon: 'logout', path: '/logout' },
]);

const handleLogout = async () => {
  await store.dispatch('logout');
  router.push('/login');
};

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  localStorage.setItem('locale', locale.value);
};

const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
});

const generateUserMenu = (user, menu) => {
  const userPermissions = {};
  user.roles.forEach(role => {
    const { name, pivot } = role;
    const correctedName = name === "Foydlanauvchilar" ? "Foydalanuvchilar" : name;
    userPermissions[correctedName] = pivot.view === "1";
  });

  return menu.filter(item => userPermissions[item.title] === true);
};


const userMenu = computed(() => {
  const user = store.state.user;
  return user ? generateUserMenu(user, menu.value) : menu.value;
});

let timer;

onMounted(() => {
  const user = store.state.user;
  if (user) {
    router.push('/vparams');
  }
  fetchParameterCount();
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  updateCurrentTime();
  timer = setInterval(updateCurrentTime, 1000);
});

</script>

<style>
.custom-sidebar {
  background-color: #57534e;
}
</style>
