<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-orange-400 text-start text-base font-medium text-orange-400 bg-[#232B3E] focus:outline-none focus:text-orange-300 focus:bg-[#2A3446] focus:border-orange-500 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-400 hover:text-gray-300 hover:bg-[#232B3E] hover:border-gray-700 focus:outline-none focus:text-gray-300 focus:bg-[#232B3E] focus:border-gray-700 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
