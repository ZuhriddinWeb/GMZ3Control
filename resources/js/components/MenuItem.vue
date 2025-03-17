<template>
    <div>
      <div
        :class="[
          'menu-item',
          { 'has-children': menuItem.children, 'active': isActive },
          menuItem.path === '/logout' ? 'hover-logout' : 'hover-item'
        ]"
        @click="handleClick"
      >
        <VaIcon :name="menuItem.icon" class="icon" />
        {{ t(menuItem.title) }}
        <span v-if="menuItem.children" class="toggle-icon">
          <VaIcon :name="showChildren ? 'expand_less' : 'expand_more'" />
        </span>
      </div>
      <div v-if="menuItem.children && showChildren" class="sub-menu">
        <MenuItem
          v-for="child in menuItem.children"
          :key="child.title"
          :menuItem="child"
        />
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter, useRoute } from 'vue-router'; // Import useRouter and useRoute
  
  const props = defineProps({
    menuItem: Object
  });
  
  const { t } = useI18n();
  const router = useRouter(); // Initialize router
  const route = useRoute(); // Initialize route
  const showChildren = ref(false);
  
  // Check if the current route matches the menu item path
  const isActive = computed(() => route.path === props.menuItem.path);
  
  const handleClick = () => {
    if (props.menuItem.path) {
      router.push(props.menuItem.path);
    } else if (props.menuItem.children) {
      showChildren.value = !showChildren.value;
    }
  };
  </script>
  
  <style scoped>
  .menu-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 1rem;
    border-bottom: 1px solid #ccc; /* Border qo'shildi */
  transition: background-color 0.3s, border-color 0.3s; /* Silliq o'tish */
  }
  
  .menu-item.active {
    background-color: #154EC1; /* Highlight color for active item */
    color:white;
    border-color: #154EC1; /* Active element border rangi */
  }
  
  .menu-item:hover {
    background-color: #333; /* Hover background color */
    border-color: #222; /* Hover qilinganda border rangi */
  }
  
  .menu-item .icon {
    margin-right: 0.5rem;
  }
  
  .sub-menu {
    padding-left: 1rem;
    border-left: 2px solid #154EC1; /* Sub-menyular uchun chap border */
  margin-left: 10px;
  padding-top: 5px;
  }
  
  .toggle-icon {
    margin-left: auto;
  }
  
  .hover-item {
    /* Default hover styles for items */
  }
  .hover-item:hover {
    background-color: #154EC1;
  color: white;
  border-color: #154EC1;
  }
  
  .hover-logout {
    /* Default styles for logout item */
  }
  .hover-logout:hover {
    background-color: rgb(220, 38, 38);
  color: white;
  border-color: rgb(180, 30, 30);
  }
  </style>
  