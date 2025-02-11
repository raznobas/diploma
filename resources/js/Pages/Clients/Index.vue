<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {Head, router, useForm, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ref} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Pagination from "@/Components/Pagination.vue";
import ClientModal from "@/Components/ClientModal.vue";
import dayjs from "dayjs";
import { useToast } from "@/useToast";
import Filters from "@/Components/Filters.vue";
import InputMask from 'primevue/inputmask';
const { showToast } = useToast();

const form = useForm({
    surname: null,
    name: null,
    patronymic: null,
    birthdate: null,
    workplace: null,
    phone: null,
    email: null,
    telegram: null,
    instagram: null,
    address: null,
    gender: 'male',
    ad_source: null,
    is_lead: false,
    director_id: usePage().props.auth.director_id,
});

const props = defineProps(['clients', 'source_options', 'filter']);

const submit = () => {
    form.post(route('clients.store'), {
        onSuccess: (response) => {
            form.reset();

            if (response.props.error === 'DUPLICATE_PHONE_NUMBER') {
                showToast('Клиент или лид с таким номером телефона уже существует.', "error");
            } else {
                showToast("Клиент успешно добавлен!", "success");
            }
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
const handleClientUpdated = (updatedClient) => {
    // Обновляем данные о клиенте после того как с дочернего компонента пришел emit после обновления данных
    selectedClient.value = updatedClient;
};
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

const closeModal = () => {
    showModal.value = false;
    selectedClient.value = null;
};

// фильтрация-поиск по клиентам
const fields = [
    { name: 'client_name', label: 'Имя/Фамилия', type: 'text' },
    { name: 'patronymic', label: 'Отчество', type: 'text' },
    { name: 'birthdate', label: 'Дата рождения', type: 'date' },
    { name: 'workplace', label: 'Место работы', type: 'text' },
    { name: 'phone', label: 'Телефон', type: 'text' },
    { name: 'email', label: 'Email', type: 'email' },
    { name: 'telegram', label: 'Telegram', type: 'text' },
    { name: 'instagram', label: 'Instagram', type: 'text' },
    { name: 'address', label: 'Адрес', type: 'text' },
    { name: 'gender', label: 'Пол', type: 'select', options: [
            { value: '', label: 'Любой' },
            { value: 'male', label: 'М' },
            { value: 'female', label: 'Ж' }
        ]},
    { name: 'ad_source', label: 'Источник рекламы', type: 'text' }
];

const filterForm = useForm({
    ...Object.fromEntries(
        fields.map((field) => [field.name, (props.filter && props.filter[field.name]) || ''])
    ),
    page: props.filter.page || 1,
});

let searchTimeout = null;

const updateFilterForm = (field, value) => {
    filterForm[field] = value;
    filterForm.page = 1;

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filterForm.get(route('clients.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    }, 1000);
};

const resetFilters = () => {
    Reflect.ownKeys(filterForm).forEach(key => {
        if (typeof filterForm[key] !== 'function') {
            filterForm[key] = '';
        }
    });
    filterForm.page = 1;
    filterForm.get(route('clients.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const onPageChange = (event) => {
    filterForm.page = event.page;
    filterForm.get(route('clients.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Клиенты"/>

    <AuthenticatedLayout>
        <template #header>
            <h2>Клиенты</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-4 md:grid-cols-4 lg:grid-cols-8 gap-2 items-end">
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                        <input id="surname" type="text" v-model="form.surname" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.surname" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="name" class="text-sm font-medium text-gray-700">Имя<span class="text-red-600">*</span></label>
                        <input id="name" type="text" required v-model="form.name" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.name" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество</label>
                        <input id="patronymic" type="text" v-model="form.patronymic" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.patronymic" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="birthdate" class="text-sm font-medium text-gray-700">Дата рождения</label>
                        <input id="birthdate" type="date" v-model="form.birthdate" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.birthdate" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="workplace" class="text-sm font-medium text-gray-700">Место работы</label>
                        <input id="workplace" type="text" v-model="form.workplace" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.workplace" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                        <InputMask id="phone" v-model="form.phone" mask="+7 (999) 999-99-99" placeholder="+7" class="mt-1 p-1 border border-gray-300 rounded-md" fluid />
                        <InputError :message="form.errors.phone" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="email" class="text-sm font-medium text-gray-700">Почта</label>
                        <input id="email" type="text" v-model="form.email" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.email" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="telegram" class="text-sm font-medium text-gray-700">Телеграм</label>
                        <input id="telegram" type="text" v-model="form.telegram" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.telegram" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="instagram" class="text-sm font-medium text-gray-700">Инстаграм</label>
                        <input id="instagram" type="text" v-model="form.instagram" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.instagram" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-2">
                        <label for="address" class="text-sm font-medium text-gray-700">Адрес</label>
                        <input id="address" type="text" v-model="form.address" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.address" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="gender" class="text-sm font-medium text-gray-700">Пол</label>
                        <select id="gender" v-model="form.gender" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option value="male">М</option>
                            <option value="female">Ж</option>
                        </select>
                        <InputError :message="form.errors.gender" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col max-sm:col-span-3">
                        <label for="ad_source" class="text-sm font-medium text-gray-700">Источник</label>
                        <select id="ad_source" v-model="form.ad_source" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="source in source_options.filter(c => c.type === 'ad_source')"
                                    :value="source.name" :key="source.id">{{ source.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.ad_source" class="mt-2 text-sm text-red-600"/>
                    </div>
                </div>
                <div class="mt-4 flex">
                    <PrimaryButton :disabled="form.processing">Добавить клиента</PrimaryButton>
                    <SecondaryButton class="ml-2" type="button" @click="form.reset()">Очистить</SecondaryButton>
                </div>
            </form>
            <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Список клиентов вашей организации</h3>
            <Filters
                :fields="fields"
                :filterForm="filterForm"
                @update:filterForm="updateFilterForm"
                @resetFilters="resetFilters"
            />
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Фамилия</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Отчество</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата рождения</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Телефон</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Почта</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="client in clients.data" :key="client.id">
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.surname }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.name }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.patronymic }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ client.birthdate ? dayjs(client.birthdate).format('DD.MM.YYYY') : '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.phone }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ client.email }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button @click="openModal(client.id)" class="text-indigo-600 hover:text-indigo-900">Карточка</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :rows="clients.per_page"
                :totalRecords="clients.total"
                :first="(clients.current_page - 1) * clients.per_page"
                @page="onPageChange"
            />
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
</style>
