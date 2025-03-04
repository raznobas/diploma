<script setup>
import dayjs from "dayjs";
import {Head, router, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import {useToast} from "@/useToast";
import axios from "axios";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const {showToast} = useToast();

const props = defineProps(['users', 'sortByRole', 'sortByClients', 'sortBySales']);

// Создаем form вне функций
const form = useForm({
    role: props.sortByRole,
    clients: null,
    sales: null,
});

const deleteUser = async (userId) => {
    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
        try {
            await axios.post(route('users.destroy', {user: userId}));
            showToast("Пользователь успешно удален!", "success");
            props.users.data = props.users.data.filter(user => user.id !== userId);
        } catch (error) {
            showToast("Ошибка при удалении пользователя.", "error");
        }
    }
};

const sortBy = (field, value) => {
    // Обновляем свойства form
    form[field] = value;
    form.get(route('users.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetSorting = () => {
    // Сбрасываем свойства form
    form.role = null;
    form.clients = null;
    form.sales = null;
    form.get(route('users.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const onPageChange = (event) => {
    const newPage = event.page;
    form.get(route('users.index', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Пользователи"/>

    <AuthenticatedLayout>
        <template #header>
            <h2>Пользователи</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Список всех пользователей</h3>
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <label for="role" class="mr-2">Сортировать по роли:</label>
                    <select id="role" :value="sortByRole" @change="sortBy('role', $event.target.value)"
                            class="p-1 px-2 pe-8 border border-gray-300 rounded-md">
                        <option value="">Все</option>
                        <option value="manager">Менеджер</option>
                        <option value="director">Директор</option>
                    </select>
                </div>
                <div>
                    <secondary-button @click="resetSorting">Сбросить сортировку</secondary-button>
                </div>
            </div>
            <div v-if="users.data && users.data.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Имя
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Роль
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Почта
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Дата регистрации
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex align-middle items-center">
                                    Клиентов
                                    <button @click="sortBy('clients', sortByClients === 'asc' ? 'desc' : 'asc')">
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
                                <div class="flex align-middle items-center">
                                    Продаж
                                    <button @click="sortBy('sales', sortBySales === 'asc' ? 'desc' : 'asc')">
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
                        <tr v-for="user in users.data" :key="users.id">
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.id }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span v-for="(role, index) in user.roles" :key="index">
                                  <span v-if="role === 'manager'">
                                      Менеджер
                                  </span>
                                  <span v-else-if="role === 'director'">
                                      Директор
                                  </span>
                                    <span v-if="index < user.roles.length - 1">, </span>
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ user.email }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ user.created_at ? dayjs(user.created_at).format('DD.MM.YYYY') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ user.total_clients }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ user.total_sales }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-800">Удалить
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :rows="users.per_page"
                            :totalRecords="users.total"
                            :first="(users.current_page - 1) * users.per_page"
                            @page="onPageChange"/>
            </div>
            <div v-else class="text-gray-500">
                Ничего не найдено
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
