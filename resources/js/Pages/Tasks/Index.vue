<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import {Head, router, useForm, usePage} from "@inertiajs/vue3";
import ClientModal from "@/Components/ClientModal.vue";
import {onMounted, ref} from "vue";
import Pagination from "@/Components/Pagination.vue";
import {useToast} from "@/useToast";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const {showToast} = useToast();

const props = defineProps(['tasks', 'noShowLeads', 'trialLessThanMonth', 'renewals']);

const showModal = ref(false);
const selectedClient = ref(null);

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

const form = useForm({})
const deleteTask = (taskId) => {
    console.log(taskId);
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        form.delete(route('tasks.destroy', taskId), {
            onSuccess: () => {
                showToast("Задача успешно удалена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};

// модальное окно
const showTaskEdit = ref(false);
const formEdit = useForm({
    id: null,
    client_id: null,
    director_id: usePage().props.auth.director_id,
    user_sender_id: usePage().props.auth.user.id,
    task_description: null,
    task_date: null,
});
const openEditModal = (task) => {
    formEdit.id = task.id;
    formEdit.client_id = task.client.id;
    formEdit.task_description = task.task_description;
    formEdit.task_date = task.task_date;
    showTaskEdit.value = true;
};
const submitEditTask = () => {
    formEdit.put(route('tasks.update', {id: formEdit.id}), {
        onSuccess: () => {
            formEdit.reset();
            showTaskEdit.value = false;
            showToast("Задача успешно обновлена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

const onPageChange = (event) => {
    const newPage = event.page;
    router.get(route('tasks.index', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Задачи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <div>
                <h3 class="mb-4 text-lg font-medium text-gray-900">Список всех задач по всем клиентам/лидам</h3>
                <div class="max-lg:overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Дата
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Описание задачи
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Отправитель
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
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
                                <button @click="openModal(task.client.id)" class="text-indigo-600 hover:text-indigo-900">
                                    Карточка
                                </button>
                                <span class="ms-4">
                                <button title="Редактировать" type="button" @click="openEditModal(task)" class="px-1">
                                    <i class="fa fa-pencil text-blue-600" aria-hidden="true"></i>
                                </button>
                                <button @click="deleteTask(task.id)" class="px-1 ms-1" title="Удалить задачу">
                                    <i class="fa fa-trash text-red-600" aria-hidden="true"></i>
                                </button>
                            </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <Pagination
                        :rows="tasks.per_page"
                        :totalRecords="tasks.total"
                        :first="(tasks.current_page - 1) * tasks.per_page"
                        @page="onPageChange"
                    />
                </div>
            </div>
            <Modal :show="showTaskEdit" @close="showTaskEdit = false">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                <span>Редактирование задачи</span>
                            </h3>
                            <div class="mt-4">
                                <form @submit.prevent="submitEditTask">
                                    <div class="mt-2 flex">
                                        <h3 class="text-md font-medium mr-2">Задача на дату</h3>
                                        <div class="flex w-32">
                                            <input type="date"
                                                   v-model="formEdit.task_date"
                                                   class="p-0 pl-1 border border-gray-300 rounded-md" required
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <textarea rows="5"
                                                  v-model="formEdit.task_description"
                                                  class="w-full p-1 border border-gray-300 rounded-md text-sm"
                                                  placeholder="Введите задачу по лиду/клиенту" required>
                                        </textarea>
                                    </div>
                                    <primary-button class="mt-2" type="submit">Редактировать задачу</primary-button>
                                    <secondary-button class="ms-2" type="button" @click="showTaskEdit = false">
                                        Отменить
                                    </secondary-button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </Modal>
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated"/>
        </div>
    </AuthenticatedLayout>
</template>
