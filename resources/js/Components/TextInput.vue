<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-lg border-[#232B3E] bg-[#232B3E] text-white placeholder-gray-400 shadow-sm focus:border-orange-400 focus:ring-orange-400 w-full px-4 py-3"
        v-model="model"
        ref="input"
    />
</template>
