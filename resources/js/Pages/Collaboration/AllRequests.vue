<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import {useToast} from "@/useToast";
import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
const {showToast} = useToast();

const props = defineProps(['allRequests']);

const form = useForm({
});

// Функции для одобрения и отказа заявки
const approveRequest = (requestId) => {
    if (confirm("Вы уверены, что хотите одобрить эту заявку?")) {
        form.post(route('collaboration.approve-request', {id: requestId}), {
            onSuccess: () => {
                showToast("Заявка успешно одобрена.", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};
const rejectRequest = (requestId) => {
    if (confirm("Вы уверены, что хотите отклонить эту заявку?")) {
        form.post(route('collaboration.reject-request', {id: requestId}), {
            onSuccess: () => {
                showToast("Заявка успешно отклонена.", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};

// Удаление менеджера из команды
const deleteManager = (managerId) => {
    if (confirm("Вы уверены, что хотите удалить этого менеджера? Ему больше не будут доступны данные о вашей организации")) {
        form.post(route('collaboration.delete-manager', {id: managerId}), {
            onSuccess: () => {
                showToast("Менеджер успешно удален!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};

const onPageChange = (event) => {
    const newPage = event.page;
    router.get(route('collaboration.index', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Все заявки"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Заявки на вступление в вашу команду</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="allRequests.data && allRequests.data.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email менеджера</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата подачи заявки</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="request in allRequests.data" :key="request.id">
                                <td class="px-3 py-2 whitespace-nowrap">{{ request.id }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ request.manager_email }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                <span v-if="request.status === 'pending'">
                                    В ожидании
                                </span>
                                    <span v-else-if="request.status === 'approved'">
                                    Одобрено
                                </span>
                                    <span v-else-if="request.status === 'rejected'">
                                    Отклонено
                                </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ request.created_at ? dayjs(request.created_at).format('DD.MM.YYYY') : '' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                <span v-if="request.status === 'pending'">
                                    <button @click="approveRequest(request.id)" class="text-green-600">Одобрить</button>
                                    <button @click="rejectRequest(request.id)" class="text-red-600 ml-3">Отклонить</button>
                                </span>
                                    <span v-else-if="request.status === 'approved'">
                                    <button @click="deleteManager(request.manager_id)" class="text-red-600">Удалить</button>
                                </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <Pagination
                            :rows="allRequests.per_page"
                            :totalRecords="allRequests.total"
                            :first="(allRequests.current_page - 1) * allRequests.per_page"
                            @page="onPageChange"
                        />
                    </div>
                    <div v-else class="text-gray-500">
                        Ничего не найдено
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
