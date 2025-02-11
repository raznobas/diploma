<script setup>
import dayjs from "dayjs";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";
import Pagination from "@/Components/Pagination.vue";
import {useToast} from "@/useToast.js";
import {Head, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const {showToast} = useToast();

const props = defineProps({
    clientsToRenewal: Object,
    filter: String,
    date: String,
});

const showModal = ref(false);
const selectedClient = ref(null);

const openModal = async (clientId) => {
    try {
        selectedClient.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        showToast("Ошибка получения данных: " + error.message, "error");
    }
};
const handleClientUpdated = (updatedClient) => {
    // Обновляем данные о клиенте после того как с дочернего компонента пришел emit после обновления данных
    selectedClient.value = updatedClient;
};

const closeModal = () => {
    showModal.value = false;
    selectedClient.value = null;
};

const form = useForm({
    filter: props.filter,
    date: props.date
});

const sortBy = (field, value) => {
    if (field === 'end_date') {
        form.date = value;
    } else {
        form.filter = field === 'filter' ? value : form.filter;
    }
    form.get(route('tasks.renewals'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const onPageChange = (event) => {
    const newPage = event.page;
    form.get(route('tasks.renewals', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Продление"/>

    <AuthenticatedLayout>
        <template #header>
            <h2>Продление</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Список клиентов с абонементом истекающим и истекшим в
                течении
                1-го месяца</h3>
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <label for="role" class="mr-2">Сортировать по:</label>
                    <select id="role" v-model="form.filter" @change="sortBy('filter', $event.target.value)"
                            class="p-1 px-2 pe-8 border border-gray-300 rounded-md">
                        <option value="expired">Истекшим в течении 1-го месяца</option>
                        <option value="upcoming">Истекающим в течении недели</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table v-if="clientsToRenewal && clientsToRenewal.data" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Фамилия
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата
                            рождения
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Телефон
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Почта
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Абонемент
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex align-middle items-center">
                                <span v-if="form.filter === 'expired'">Истек</span>
                                <span v-else-if="form.filter === 'upcoming'">Истекает</span>
                                <button @click="sortBy('end_date', date === 'asc' ? 'desc' : 'asc')">
                                     <span>
                                         <svg width="20" version="1.1"
                                              xmlns="http://www.w3.org/2000/svg"
                                              xmlns:xlink="http://www.w3.org/1999/xlink"
                                              viewBox="0 0 16 16"><path
                                             fill="#444444" d="M11 7h-6l3-4z"/><path
                                             fill="#444444" d="M5 9h6l-3 4z"/></svg>
                                    </span>
                                </button>
                            </div>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="client in clientsToRenewal.data" :key="client.id">
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.surname }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.name }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ client.birthdate ? dayjs(client.birthdate).format('DD.MM.YYYY') : '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.phone }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.email }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span v-if="client.service_type === 'group'">Групповая</span>
                            <span v-if="client.service_type === 'minigroup'">Минигруппа</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{
                                client.subscription_end_date ? dayjs(client.subscription_end_date).format('DD.MM.YYYY') : ''
                            }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button @click="openModal(client.id)" class="text-indigo-600 hover:text-indigo-900">Карточка
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :rows="clientsToRenewal.per_page"
                :totalRecords="clientsToRenewal.total"
                :first="(clientsToRenewal.current_page - 1) * clientsToRenewal.per_page"
                @page="onPageChange"
            />
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated"/>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
