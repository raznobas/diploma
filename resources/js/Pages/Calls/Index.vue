<script setup>

import {Head, router, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
import axios from "axios";
import {ref} from "vue";
import ClientModal from "@/Components/ClientModal.vue";
import {useToast} from "@/useToast.js";
import ClientLeadForm from "@/Components/ClientLeadForm.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Spinner from "@/Components/Spinner.vue";
import Tooltip from "@/Components/Tooltip.vue";
const {showToast} = useToast();

const props = defineProps(['calls']);

const showModal = ref(false);
const selectedClientCard = ref(null);
const showLeadModal = ref(false);
const leadsCall = ref(null);
const isLoading = ref(false);

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

const closeModal = () => {
    showModal.value = false;
    showLeadModal.value = false;
    selectedClientCard.value = null;
};

const createLead = (formData, callId) => {
    formData.is_lead = true;

    // Проверяем, установил ли менеджер значение ad_source в форме, и если нет, то записываем туда "ЗВОНОК"
    if (!formData.ad_source) {
        formData.ad_source = 'ЗВОНОК';
    }

    callId = leadsCall.value.id;

    // Передаем callId в маршрут
    formData.post(route('clients.store', {callId}), {
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
        case 'appeared':
        case 'connected':
            return 'Новый';
        case 'disconnected':
            return 'Вызов завершен';
        default:
            return '-';
    }
}

const formatDuration = (duration) => {
    if (!duration || duration === 0) {
        return 0;
    }
    const minutes = Math.floor(duration / 60);
    const seconds = duration % 60;
    return `${minutes} м. ${seconds} с.`;
};

// Метод для форматирования времени с добавлением 3 часов (Москва, UTC+3)
const formatMoscowTime = (datetime) => {
    return dayjs(datetime).add(3, 'hour').format('DD.MM.YYYY HH:mm');
};

const refreshCalls = () => {
    isLoading.value = true;

    router.get(route('calls.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            showToast("Ошибка при обновлении данных", "error");
        },
    });
};

const onPageChange = (event) => {
    const newPage = event.page;
    router.get(route('calls.index', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};

// галочка нецеловой звонок
const toggleIrrelevant = async (callId) => {
    try {
        // Находим звонок в списке
        const call = props.calls.data.find((call) => call.id === callId);
        if (!call) return;

        // Инвертируем значение is_irrelevant
        call.is_irrelevant = !call.is_irrelevant;

        await axios.patch(`/calls/${callId}/toggle-irrelevant`, {
            is_irrelevant: call.is_irrelevant,
        });
    } catch (error) {
        console.error('Ошибка при обновлении состояния:', error);
        showToast("Ошибка при изменении состояния: " + error.message, "error");
    }
};
</script>

<template>
    <Head title="Звонки"/>
    <AuthenticatedLayout>
        <template #header>
            <h2>Звонки</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <div>
                <h3 class="mb-4 text-lg font-medium text-gray-900">Список звонков</h3>
                <ClientModal :show="showModal" :client="selectedClientCard"
                             @close="closeModal"/>
                <Modal :show="showLeadModal" @close="closeModal">
                    <ClientLeadForm
                        :is-lead="true"
                        :initial-phone="leadsCall.phone_from"
                        @submit="createLead"
                    />
                </Modal>
                <PrimaryButton
                    @click="refreshCalls"
                    class="mb-4"
                    :disabled="isLoading"
                >
                    <span v-if="isLoading" class="inline-flex items-center">
                      <Spinner class="me-2"/>
                      Обновление...
                    </span>
                    <span v-else>Обновить</span>
                </PrimaryButton>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Время
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                От кого
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Кому
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Длительность
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Статус
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Нецелевой
                                    <Tooltip class="ms-1" content="Отметьте для спама или нецелевых звонков."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="call in calls.data" :key="call.id"
                            :class="{'bg-red-100': call.status === 'missed', 'bg-green-100': call.status === 'answered'}">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ call.call_time ? formatMoscowTime(call.call_time) : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ call.phone_from }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ call.phone_to }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ formatDuration(call.duration) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ getStatusText(call.status) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :checked="call.is_irrelevant"
                                    @change="toggleIrrelevant(call.id)"
                                    class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out"
                                />
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <button v-if="call.client_id" @click="openModal(call.client_id)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Карточка
                                </button>
                                <button v-else @click="openLeadModal(call)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Создать лид
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    :rows="calls.per_page"
                    :totalRecords="calls.total"
                    :first="(calls.current_page - 1) * calls.per_page"
                    @page="onPageChange"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
