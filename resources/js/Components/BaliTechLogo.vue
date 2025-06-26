<template>
  <div class="flex flex-col items-center">
    <img 
      :src="logoSrc" 
      alt="BaliTech Pvt Ltd" 
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'normal',
    validator: (value) => ['normal', 'revert'].includes(value)
  }
})
const logoSrc = computed(() => {
  return props.type === 'revert' 
    ? '/images/logo/bt-logo-invert.png' 
    : '/images/logo/bt-logo.png'
})
</script> 