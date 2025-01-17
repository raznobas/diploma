<script setup>

import dayjs from "dayjs";
import {Head} from "@inertiajs/vue3";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ref} from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "@/useToast.js";
const {showToast} = useToast();

const props = defineProps(['categories']);
const isLoading = ref(false);
const startDate = ref(dayjs().subtract(1, 'month').format('YYYY-MM-DD'));
const endDate = ref(dayjs().format('YYYY-MM-DD'));

// Состояния для выбранных категорий
const selectedCategories = ref({
    sport_type: [],
    product_type: [],
    training_count: [],
    subscription_duration: [],
    visits_per_week: [],
    trainer: [],
    trainer_category: [],
    pay_method: [],
    service_type: [],
});

// Типы категорий
const types = [
    {name: 'service_type', title: 'Вид услуги'},
    {name: 'sport_type', title: 'Виды спорта'},
    {name: 'product_type', title: 'Виды товаров'},
    {name: 'training_count', title: 'Кол-во тренировок'},
    {name: 'subscription_duration', title: 'Длительность абонементов'},
    {name: 'visits_per_week', title: 'Кол-во посещений в неделю'},
    {name: 'trainer', title: 'Тренеры'},
    {name: 'trainer_category', title: 'Категории тренеров'},
    {name: 'pay_method', title: 'Способы оплаты'},
];

const serviceTypeOptions = [
    {value: 'trial', label: 'Пробная'},
    {value: 'group', label: 'Групповая'},
    {value: 'minigroup', label: 'Минигруппа'},
    {value: 'individual', label: 'Индивидуальная'},
    {value: 'split', label: 'Сплит'},
];

// Функция для выбора всех категорий в списке
const toggleAllCategories = (type) => {
    let itemsToToggle;

    if (type === 'service_type') {
        // Для "Вид услуги" используем фиксированные параметры
        itemsToToggle = serviceTypeOptions.map(option => option.value);
    } else {
        // Для остальных категорий используем данные из props.categories
        itemsToToggle = props.categories
            .filter(c => c.type === type)
            .map(c => c.name);
    }

    // Проверяем, все ли элементы уже выбраны
    const allSelected = itemsToToggle.every(item => selectedCategories.value[type].includes(item));

    if (allSelected) {
        // Если все уже выбраны, снимаем выбор
        selectedCategories.value[type] = [];
    } else {
        // Иначе выбираем все
        selectedCategories.value[type] = itemsToToggle;
    }
};

const exportData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.post(route('export'), {
            start_date: startDate.value,
            end_date: endDate.value,
            categories: selectedCategories.value,
        }, {
            responseType: 'blob', // Указываем, что ожидаем бинарные данные (файл)
        });

        // Создаем ссылку для скачивания файла
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'export.xlsx'); // Имя файла
        document.body.appendChild(link);
        link.click();

        // Удаляем ссылку после скачивания
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        showToast("Файл успешно скачан!", "success");
    } catch (error) {
        if (error.response && error.response.status === 404) {
            // Если сервер вернул 404, пытаемся прочитать сообщение об ошибке
            const reader = new FileReader();
            reader.onload = () => {
                const errorData = JSON.parse(reader.result);
                showToast(errorData.error, "error");
            };
            reader.readAsText(error.response.data);
        } else {
            console.error('Ошибка при экспорте:', error);
            showToast("Произошла ошибка при экспорте данных", "error");
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Экспорт в Excel"/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Экспорт в Excel</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="mb-2 text-md font-medium text-gray-900">Выберите диапазон: </h4>
                    <div class="mb-4 flex items-center flex-wrap gap-3">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Дата от:</label>
                            <input
                                type="date"
                                v-model="startDate"
                                id="start_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">Дата до:</label>
                            <input
                                type="date"
                                v-model="endDate"
                                id="end_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                    <h4 class="mb-2 text-md font-medium text-gray-900">Выберите категории: </h4>
                    <div v-for="type in types" :key="type.name" class="mb-4">
                        <div class="flex items-center mb-2 gap-2">
                            <h4 class="text-sm font-medium text-gray-700">{{ type.title }}</h4>
                            <button
                                @click="toggleAllCategories(type.name)"
                                class="text-sm text-indigo-600 hover:text-indigo-500 focus:outline-none"
                            >
                                Выбрать все
                            </button>
                        </div>
                        <div class="grid sm:grid-cols-5 grid-cols-2 gap-2">
                            <div v-if="type.name === 'service_type'">
                                <div v-for="option in serviceTypeOptions" :key="option.value">
                                    <label class="inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="selectedCategories[type.name]"
                                            :value="option.value"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">{{ option.label }}</span>
                                    </label>
                                </div>
                            </div>
                            <!-- Для категорий, которые берутся из $page.props.categories -->
                            <div v-if="type.name !== 'service_type'"
                                 v-for="category in $page.props.categories.filter(c => c.type === type.name)"
                                 :key="category.id">
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="selectedCategories[type.name]"
                                        :value="category.name"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">{{ category.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <PrimaryButton
                        type="button"
                        @click="exportData"
                        :disabled="isLoading"
                    >
                      <span v-if="isLoading" class="inline-flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                  stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Экспорт...
                      </span>
                        <span v-else>
                        Экспорт данных
                      </span>
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
