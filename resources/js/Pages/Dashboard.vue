<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';
import NavLink from "@/Components/NavLink.vue";

</script>

<template>
    <Head title="Главная" />

    <AuthenticatedLayout>
        <template #header>
            <h2>Главная</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="$page.props.auth.role === 'manager' && !$page.props.auth.director_id" class="p-6 text-gray-900">На данный момент вам недоступен функционал для работы с CRM системой,
                        <br>
                        подайте заявку на прикрепление к вашему директору на
                        <NavLink :href="route('collaboration.send-request')">специальной странице</NavLink>
                    </div>
                    <div v-else class="p-6 text-gray-900">
                        <div v-if="$page.props.auth.role === 'admin'">
                            <h3 class="text-lg font-semibold">Добро пожаловать в административную панель CRM системы!</h3>
                        </div>
                        <div v-else>
                            <h3 class="text-lg font-semibold mb-4">Добро пожаловать в CRM систему вашего боксерского клуба!</h3>
                            <p class="mb-4">Здесь вы можете управлять своими клиентами, лидами, продажами и другими важными данными.  <br>
                                Для этого воспользуйтесь меню навигации</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
