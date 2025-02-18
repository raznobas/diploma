<script setup>
import {Head, useForm, usePage, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "@/useToast.js";
import Pagination from "@/Components/Pagination.vue";
import dayjs from "dayjs";
import {ref} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Tooltip from "@/Components/Tooltip.vue";

const {showToast} = useToast();

const props = defineProps(['categories', 'massMailings']);
const editingMailingId = ref(null); // ID редактируемой записи

const sendTimes = [
    {value: 'after1day', label: 'На следующий день'},
    {value: 'after1week', label: 'Через неделю'},
    {value: 'after1month', label: 'Через месяц'},
];

// Функция перевода значений offset в русские названия
const formatSendOffset = (offsetJson) => {
    try {
        const offsets = JSON.parse(offsetJson);
        return offsets
            .map((offset) => sendTimes.find((t) => t.value === offset)?.label || offset)
            .join(", ");
    } catch (e) {
        return "-";
    }
};

const form = useForm({
    block: 'trials',
    selected_categories: {
        sport_type: [],
        service_type: [],
    },
    message_text: '',
    send_offset: [],
    director_id: usePage().props.auth.director_id,
});

const deleteMailing = (mailingId) => {
    if (confirm('Вы уверены, что хотите удалить эту настройку?')) {
        form.delete(route('mass-mailing.destroy', mailingId), {
            onSuccess: () => {
                showToast("Настройка успешно удалена!", "success");
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
    router.get(route('mass-mailing.index', { page: newPage }), {
        preserveState: true,
        preserveScroll: true,
    });
};

// Функция для подстановки данных в форму при редактировании
const editMailing = (mailing) => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    editingMailingId.value = mailing.id; // Запоминаем ID редактируемой записи

    form.block = mailing.block;
    form.selected_categories = JSON.parse(mailing.selected_categories);
    form.message_text = mailing.message_text;
    form.send_offset = JSON.parse(mailing.send_offset);
};

// Функция для сохранения/обновления рассылки
const saveMassMailing = () => {
    // Преобразуем сложные объекты в JSON
    form.selected_categories = JSON.stringify(form.selected_categories);
    form.send_offset = JSON.stringify(form.send_offset);

    if (editingMailingId.value === null) {
        // СОЗДАНИЕ НОВОЙ РАССЫЛКИ
        form.post(route("mass-mailing.store"), {
            onSuccess: () => {
                form.reset();
                showToast("Настройка рассылки успешно сохранена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach((error) => showToast(error, "error"));
            },
        });
    } else {
        // ОБНОВЛЕНИЕ СУЩЕСТВУЮЩЕЙ РАССЫЛКИ
        form.put(route("mass-mailing.update", { id: editingMailingId.value }), {
            onSuccess: () => {
                form.reset();
                editingMailingId.value = null; // Сбрасываем режим редактирования
                showToast("Настройка рассылки успешно обновлена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach((error) => showToast(error, "error"));
            },
        });
    }
};
</script>

<template>
    <Head title="Массовые рассылки"/>
    <AuthenticatedLayout>
        <template #header>
            <h2>Массовые рассылки</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <!-- Выбор блока рассылки -->
            <div class="mb-4 flex flex-wrap items-center">
                <div class="w-full sm:w-auto">
                    <label for="block" class="mr-2">Блок рассылки:</label>
                </div>
                <div class="w-full sm:w-auto">
                    <select id="block" v-model="form.block"
                            class="p-1 px-2 pe-8 border border-gray-300 rounded-md max-w-full">
                        <option value="" selected disabled hidden>– Выберите блок рассылки –</option>
                        <option value="trials">Пробники</option>
                    </select>
                </div>
                <Tooltip class=" font-medium text-gray-500 ms-2"
                         content="Рассылка для клиентов, которые прошли пробную тренировку, но не совершили какую-либо покупку услуг. "/>
            </div>

            <!-- Основной контейнер с двумя панелями -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div v-if="form.block">
                    <div class="flex flex-wrap">
                        <!-- Левая панель: Фильтры -->
                        <div class="w-full md:w-1/4 border-r border-gray-200 pr-4">
                            <h2 class="text-xl font-semibold mb-4">Фильтры</h2>
                            <div class="flex gap-3 mb-3">
                                <div>
                                    <label class="block mb-1">Виды спорта:</label>
                                    <div v-for="category in categories"
                                         :key="category.id">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="category.name"
                                                v-model="form.selected_categories.sport_type"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">{{ category.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Правая панель: Настройка сообщений -->
                        <div class="w-full md:w-3/4 pl-4">
                            <h2 class="text-xl font-semibold mb-4">Настройка сообщений</h2>
                            <div class="mb-6 p-4 border border-gray-200 rounded-md">
                                <!-- Поле для ввода текста сообщения -->
                                <div class="mb-2">
                                    <label class="block mb-1">Текст сообщения:</label>
                                    <textarea v-model="form.message_text"
                                              class="w-full border border-gray-300 rounded-md p-2" rows="3"
                                              placeholder="Введите текст сообщения"></textarea>
                                </div>
                                <!-- Выбор времени отправки -->
                                <div class="mb-2">
                                    <label class="block mb-1">Периоды отправки сообщений (смещение относительно даты пробной тренировки)*:</label>
                                    <div v-for="time in sendTimes" :key="time.value">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="time.value"
                                                v-model="form.send_offset"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">{{ time.label }}</span>
                                        </label>
                                    </div>
                                    <div class="text-xs text-gray-700 mt-2">* Отправка сообщений происходит в 16:00 по московскому времени</div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <PrimaryButton @click="saveMassMailing" :disabled="form.processing">
                                    {{ editingMailingId ? "Обновить" : "Сохранить" }}
                                </PrimaryButton>
                                <SecondaryButton v-if="editingMailingId"
                                                 @click="editingMailingId = null; form.reset();">
                                    Отмена
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p class="text-gray-500">Пожалуйста, выберите блок рассылки для начала настройки.</p>
                </div>
            </div>

            <!-- Таблица с массовыми рассылками -->
            <h3 class="mb-4 mt-8 text-lg font-medium text-gray-900">Список настроек рассылок</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Создана
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Текст
                            сообщения
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Категории
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Периоды отправки
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="mailing in massMailings.data" :key="mailing.id">
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ mailing.created_at ? dayjs(mailing.created_at).format('DD.MM.YYYY') : '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-normal">{{ mailing.message_text }}</td>
                        <td class="px-3 py-2 whitespace-normal">
                        <span v-if="JSON.parse(mailing.selected_categories).sport_type.length > 0">
                            {{ JSON.parse(mailing.selected_categories).sport_type.join(', ') }}
                        </span>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-3 py-2 whitespace-normal">
                            <span v-if="mailing.send_offset">
                                {{ formatSendOffset(mailing.send_offset) }}
                            </span>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <button title="Редактировать" type="button" @click="editMailing(mailing)" class="px-1">
                                <i class="fa fa-pencil text-blue-600" aria-hidden="true"></i>
                            </button>
                            <button @click="deleteMailing(mailing.id)" class="px-1 ms-1" title="Удалить рассылку">
                                <i class="fa fa-trash text-red-600" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                :rows="massMailings.per_page"
                :totalRecords="massMailings.total"
                :first="(massMailings.current_page - 1) * massMailings.per_page"
                @page="onPageChange"
            />
        </div>
    </AuthenticatedLayout>
</template>


