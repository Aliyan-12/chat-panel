<script setup>
import BaliTechLogo from '@/Components/BaliTechLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#0A1526] pt-6 sm:justify-center sm:pt-0"
    >
        <div class="w-64 h-64">
            <BaliTechLogo />
        </div>

        <div
            class="w-full overflow-hidden bg-[#0A1526] px-6 py-4 shadow-lg sm:max-w-md sm:rounded-lg border border-[#232B3E]"
        >
            <slot />
        </div>
        <div class="mt-6 text-gray-400 text-sm">
            © {{ new Date().getFullYear() }} BaliTech. All rights reserved.
        </div>
    </div>
</template>
