<script setup>
import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";
import {Head, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    trialsLessThanMonth: {
        type: Object,
    },
});

const showModal = ref(false);
const selectedClient = ref(null);

const openModal = async (clientId) => {
    try {
        selectedClient.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        console.error('Ошибка при получении данных клиента:', error);
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

const onPageChange = (event) => {
    const newPage = event.page;
    router.get(route('tasks.trialsLessThanMonth', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Пробники"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Список пробников в течении последнего месяца, без
                активного абонемента</h3>
            <div class="max-lg:overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата
                            пробной
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="client in trialsLessThanMonth.data" :key="client.id">
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ client.name }} {{ client.surname }}
                        </td>
                        <td class="px-3 py-2 whitespace-normal">
                            {{ client.training_date ? dayjs(client.training_date).format('DD.MM.YYYY') : '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button @click="openModal(client.id)" class="text-indigo-600 hover:text-indigo-900">Карточка
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <Pagination
                    :rows="trialsLessThanMonth.per_page"
                    :totalRecords="trialsLessThanMonth.total"
                    :first="(trialsLessThanMonth.current_page - 1) * trialsLessThanMonth.per_page"
                    @page="onPageChange"
                />
            </div>
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated"/>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
</style>
