<script setup>
import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";
import {Head, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import customParseFormat from 'dayjs/plugin/customParseFormat';
import {useToast} from "@/useToast.js";
const {showToast} = useToast();
dayjs.extend(customParseFormat);

const props = defineProps({
    noShowLeads: {
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
    router.get(route('tasks.noShowLeads', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteAppointment = async (appointmentId) => {
    if (confirm('Вы уверены, что хотите удалить эту запись?')) {
        router.delete(route('leads.destroy', appointmentId), {
            onSuccess: () => {
                showToast("Запись успешно удалена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Непришедшие на пробную"/>

    <AuthenticatedLayout>
        <template #header>
            <h2>Непришедшие на пробную</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Список лидов не пришедших на пробную тренировку</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Телефон</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид спорта</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид услуги</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тренер</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата/время</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="appointment in noShowLeads.data" :key="appointment.id">
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ appointment.client.name }} {{ appointment.client.surname }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ appointment.client.phone }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ appointment.sport_type }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span v-if="appointment.service_type === 'group'">Групповая</span>
                            <span v-else-if="appointment.service_type === 'minigroup'">Минигруппа</span>
                            <span v-else-if="appointment.service_type === 'individual'">Индивидуальная</span>
                            <span v-else-if="appointment.service_type === 'split'">Сплит</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ appointment.trainer }}</td>
                        <td class="px-3 py-2 whitespace-normal">
                            {{ appointment.training_date ? dayjs(appointment.training_date).format('DD.MM.YYYY') : '' }}
                            <span v-if="appointment.training_time">/
                              {{ dayjs(appointment.training_time, "HH:mm:ss").format('HH:mm') }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button @click="openModal(appointment.client_id)" class="text-indigo-600 hover:text-indigo-900">
                                Карточка
                            </button>
                            <span class="ms-4">
                                <button @click="deleteAppointment(appointment.id)" class="px-1 ms-1"
                                        title="Удалить запись">
                                    <i class="fa fa-trash text-red-600" aria-hidden="true"></i>
                                </button>
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :rows="noShowLeads.per_page"
                :totalRecords="noShowLeads.total"
                :first="(noShowLeads.current_page - 1) * noShowLeads.per_page"
                @page="onPageChange"
            />
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated"/>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
</style>
