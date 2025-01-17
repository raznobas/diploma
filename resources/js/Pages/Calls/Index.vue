<script setup>

import {Head, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
import axios from "axios";
import {ref} from "vue";
import ClientModal from "@/Components/ClientModal.vue";
import {useToast} from "@/useToast.js";
import ClientLeadForm from "@/Components/ClientLeadForm.vue";
import Modal from "@/Components/Modal.vue";
const {showToast} = useToast();

const props = defineProps(['calls']);

const showModal = ref(false);
const selectedClientCard = ref(null);
const showLeadModal = ref(false);
const leadsCall = ref(null);

const openModal = async (clientId) => {
    try {
        selectedClientCard.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        showToast("Ошибка получения данных: " + error.message, "error");
    }
};

const openLeadModal = (call) => {
    leadsCall.value = call;
    showLeadModal.value = true;
};

const changePage = (page) => {
    axios.get(route('calls.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const closeModal = () => {
    showModal.value = false;
    showLeadModal.value = false;
    selectedClientCard.value = null;
};

const createLead = (formData, callId) => {
    formData.is_lead = true;
    callId = leadsCall.value.id;

    // Передаем callId в маршрут
    formData.post(route('clients.store', { callId }), {
        onSuccess: () => {
            showToast("Лид успешно добавлен!", "success");
            closeModal();
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

const getStatusText = (status) => {
    switch (status) {
        case 'missed':
            return 'Пропущен';
        case 'answered':
            return 'Принят';
        case 'processing':
            return 'На линии';
        default:
            return 'Неизвестно';
    }
}
</script>

<template>
    <Head title="Звонки"/>
    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <div>
                <h3 class="mb-4 text-lg font-medium text-gray-900">Список звонков</h3>
                <ClientModal :show="showModal" :client="selectedClientCard"
                             @close="closeModal"/>
                <Modal :show="showLeadModal" @close="closeModal">
                    <ClientLeadForm
                        :is-lead="true"
                        :initial-phone="leadsCall.phone"
                        @submit="createLead"
                    />
                </Modal>
                <div class="max-lg:overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Дата
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Телефон
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Длительность
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Статус
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="call in calls.data" :key="call.id">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ call.call_time ? dayjs(call.call_time).format('DD.MM.YYYY HH:mm') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ call.phone }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ call.duration }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ getStatusText(call.status) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <button v-if="call.client_id" @click="openModal(call.client_id)" class="text-indigo-600 hover:text-indigo-900">
                                    Карточка
                                </button>
                                <button v-else @click="openLeadModal(call)" class="text-indigo-600 hover:text-indigo-900">
                                    Создать лид
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <Pagination :items="calls" page-param="page" @change-page="changePage"/>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
