<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-300 transition duration-150 ease-in-out hover:bg-[#2A3446] hover:text-orange-400 focus:bg-[#2A3446] focus:text-orange-400 focus:outline-none"
    >
        <slot />
    </Link>
</template>
