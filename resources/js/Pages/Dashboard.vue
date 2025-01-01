<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';

const gym = usePage().props.auth.gym;
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Панель управления</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 overflow-hidden shadow-sm sm:rounded-lg">
                    <h2 v-if="gym" class="text-lg font-medium text-gray-900 mb-3">Зал: {{ gym.label }}</h2>
                    <div class="text-gray-900">Вы вошли!</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
