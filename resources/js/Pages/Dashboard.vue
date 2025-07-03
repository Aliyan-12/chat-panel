<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaliTechLogo from '@/Components/BaliTechLogo.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    isAdmin: {
        type: Boolean,
    },
    isUser: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-[#181F2A] shadow-sm sm:rounded-lg border border-[#232B3E]">
                    <div class="p-6 text-gray-300">
                        <div class="flex flex-col items-center justify-center mb-6">
                            <div class="w-32 h-32 mb-4">
                                <BaliTechLogo />
                            </div>
                            <h2 class="text-2xl font-bold text-orange-400">Welcome to BaliTech Dashboard</h2>
                        </div>
                        <p class="text-center">You're logged in! Start using the chat application by clicking on the Chat link in the navigation.</p>
                    </div>
                </div>

                <pre>{{ isAdmin }}</pre>
                <div v-if="isAdmin">
                    i am only for admins
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
