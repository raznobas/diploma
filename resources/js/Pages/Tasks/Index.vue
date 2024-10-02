<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import {Head, useForm} from "@inertiajs/vue3";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";
import Pagination from "@/Components/Pagination.vue";
import NoShowLeads from "@/Pages/Tasks/Partials/NoShowLeads.vue";
import { useToast } from "@/useToast";
import axios from "axios";
import TrialsLessThanMonth from "@/Pages/Tasks/Partials/TrialsLessThanMonth.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Renewals from "@/Pages/Tasks/Partials/Renewals.vue";
const { showToast } = useToast();

const props = defineProps(['tasks', 'noShowLeads', 'trialLessThanMonth', 'renewals']);

const showModal = ref(false);
const selectedClient = ref(null);
const currentView = ref('tasks');

const openModal = async (clientId) => {
    try {
        selectedClient.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        showToast("Ошибка при получении данных: " + error.message, "error");
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
})
const deleteTask = (taskId) => {
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        try {
            form.delete(route('tasks.destroy', taskId));
            showToast("Задача успешно удалена!", "success");
        } catch (error) {
            showToast("Ошибка при удалении задачи: " + error.message, "error");
        }
    }
};

const setView = (view) => {
    currentView.value = view;
};
</script>

<template>
    <Head title="Задачи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <div class="mb-4 flex space-x-4 justify-center">
                <secondary-button
                    @click="setView('tasks')"
                    :class="{ 'active': currentView === 'tasks' }"
                    class="hover:text-indigo-900"
                >
                    Задачи
                </secondary-button>
                <secondary-button
                    @click="setView('noShowLeads')"
                    :class="{ 'active': currentView === 'noShowLeads' }"
                    class="hover:text-indigo-900"
                >
                    Не пришедшие лиды
                </secondary-button>
                <secondary-button
                    @click="setView('trialsLessThanMonth')"
                    :class="{ 'active': currentView === 'trialsLessThanMonth' }"
                    class="hover:text-indigo-900"
                >
                    Пробы менее месяца
                </secondary-button>
                <secondary-button
                    @click="setView('renewals')"
                    :class="{ 'active': currentView === 'renewals' }"
                    class="hover:text-indigo-900"
                >
                    Продление
                </secondary-button>
            </div>

            <div v-if="currentView === 'tasks'">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Список всех задач по всем клиентам/лидам</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Описание задачи</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Отправитель</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="task in tasks.data" :key="task.id">
                        <td class="px-3 py-2 whitespace-nowrap">{{ task.id }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ task.task_date ? dayjs(task.task_date).format('DD.MM.YYYY') : '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-normal">
                            {{ task.task_description }}
                        </td>
                        <td class="px-3 py-2 whitespace-normal">
                            {{ task.user_sender.name }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button @click="openModal(task.client.id)" class="text-indigo-600 hover:text-indigo-900">Карточка</button>
                            <button @click="deleteTask(task.id)" class="ms-2 px-1" title="Удалить задачу">
                                <i class="fa fa-trash text-red-600" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <Pagination :items="tasks" page-param="page"/>
            </div>

            <NoShowLeads v-if="currentView === 'noShowLeads'" :no-show-leads="noShowLeads"/>
            <TrialsLessThanMonth v-if="currentView === 'trialsLessThanMonth'" :trials="trialLessThanMonth"/>
            <Renewals v-if="currentView === 'renewals'" :clients-to-renewal="renewals"/>

            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.active {
    background-color: #6366f1;
    color: white;
}
</style>
