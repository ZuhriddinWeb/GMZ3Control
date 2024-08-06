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
      </VaNavbar>
      <VaDivider style="margin: 0" />
    </template>

    <template #left>
      <VaSidebar v-model="isSidebarVisible" class="custom-sidebar">
        <!-- Language Change Button -->
        <VaButton @click="changeLanguage" style="margin-bottom: 1rem; border-radius: 0;">
          {{ currentLanguage }}
        </VaButton>
        <!-- User Menu Items -->
        <template v-for="menuItem in userMenu" :key="menuItem.icon">
          <a v-if="menuItem.path === '/logout'" @click.prevent="handleLogout">
            <VaSidebarItem>
              <VaSidebarItemContent>
                <VaIcon :name="menuItem.icon" />
                <VaSidebarItemTitle>
                  {{ $t(menuItem.title) }} <!-- Ensure this is correct -->
                </VaSidebarItemTitle>
              </VaSidebarItemContent>
            </VaSidebarItem>
          </a>
          <router-link v-else :to="menuItem.path" class="router-link">
            <VaSidebarItem>
              <VaSidebarItemContent>
                <VaIcon :name="menuItem.icon" />
                <VaSidebarItemTitle>
                  {{ $t(menuItem.title) }} <!-- Ensure this is correct -->
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
import { ref, computed, watchEffect, onMounted } from 'vue';
import { useBreakpoint } from 'vuestic-ui';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const breakpoints = useBreakpoint();
const store = useStore();
const router = useRouter();

const isSidebarVisible = ref(breakpoints.smUp);

watchEffect(() => {
  isSidebarVisible.value = breakpoints.smUp;
});

const { t, locale } = useI18n();
const currentLanguage = computed(() => locale.value);

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  console.log('Language changed to:', locale.value);
};

const menu = [
  { title: 'menu.dashboard', icon: 'dashboard', path: '/' },
  { title: 'menu.factory', icon: 'factory', path: '/factory' },
  { title: 'menu.structure', icon: 'dashboard', path: '/structure' },
  { title: 'menu.blogs', icon: 'account_tree', path: '/blogs' },
  { title: 'menu.units', icon: 'ad_units', path: '/units' },
  { title: 'menu.graphics', icon: 'schedule', path: '/graphics' },
  { title: 'menu.graphictimes', icon: 'alarm', path: '/graphictimes' },
  { title: 'menu.paramgraphics', icon: 'schema', path: '/paramgraphics' },
  { title: 'menu.paramtypes', icon: 'format_list_numbered', path: '/paramtypes' },
  { title: 'menu.params', icon: 'format_list_bulleted', path: '/params' },
  { title: 'menu.sources', icon: 'content_copy', path: '/sources' },
  { title: 'menu.changes', icon: 'content_copy', path: '/changes' },
  { title: 'menu.vparams', icon: 'content_copy', path: '/vparams' },
  { title: 'menu.users', icon: 'person', path: '/users' },
  { title: 'menu.logout', icon: 'logout', path: '/logout' },
];

const handleLogout = async () => {
  await store.dispatch('logout');
  router.push('/login');
};

const generateUserMenu = (user, menu) => {
  const userPermissions = {};

  user.roles.forEach(role => {
    const { name, pivot } = role;
    const correctedName = name === "Foydlanauvchilar" ? "Foydalanuvchilar" : name;
    userPermissions[correctedName] = pivot.view === "1";
  });

  return menu.filter(item => userPermissions[t(item.title)] === true);
};

const userMenu = computed(() => {
  const user = store.state.user;
  console.log('User:', user);
  if (!user) return [];
  const menuItems = generateUserMenu(user, menu);
  console.log('User Menu Items:', menuItems);
  return menuItems;
});


onMounted(() => {
  const user = store.state.user;
  if (user) {
    router.push('/vparams');
  }
});
</script>

<style>
.custom-sidebar {
  background-color: #57534e;
}
</style>
