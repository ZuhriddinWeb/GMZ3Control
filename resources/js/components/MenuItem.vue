<template>
    <div>
      <div :class="['menu-item', { 'has-children': menuItem.children }]" @click="handleClick">
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
  import { ref } from 'vue';
  import { useI18n } from 'vue-i18n';
  import VaIcon from 'vuestic-ui/src/components/VaIcon/VaIcon.vue'; // Adjust import as necessary
  
  const props = defineProps({
    menuItem: Object
  });
  
  const { t } = useI18n();
  const showChildren = ref(false);
  
  const handleClick = () => {
    if (props.menuItem.path) {
      router.push(props.menuItem.path);
    } else if (props.menuItem.children) {
      showChildren.value = !showChildren.value;
    }
  };
  </script>
  
  <style>
  .menu-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 0.5rem;
    color: white;
  }
  .menu-item .icon {
    margin-right: 0.5rem;
  }
  .sub-menu {
    padding-left: 1rem;
  }
  .toggle-icon {
    margin-left: auto;
  }
  </style>
  