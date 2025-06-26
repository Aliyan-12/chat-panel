<script setup>
import ChatComponent from '@/Components/Chat/ChatComponent.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script> 

<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <div class="py-2">
            <div class="mx-auto max-w-7xl">
                <div class="overflow-hidden">
                    <ChatComponent />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>