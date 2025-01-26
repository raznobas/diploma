<template>
    <div class="custom-paginator mt-3">
        <!-- Условный рендеринг: если записей нет -->
        <div v-if="totalRecords === 0" class="text-center">
            Записи отсутствуют.
        </div>

        <!-- Условный рендеринг: если записи есть -->
        <template v-else>
            <!-- Компонент Paginator -->
            <Paginator
                :rows="rows"
                :totalRecords="totalRecords"
                :first="first"
                @page="handlePageChange"
            />

            <!-- Блок с общим количеством записей -->
            <div class="mt-1 text-center">
                Всего записей: {{ totalRecords }}
            </div>
        </template>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import Paginator from 'primevue/paginator';

// Определяем props
const props = defineProps({
    rows: {
        type: Number,
        required: true,
    },
    totalRecords: {
        type: Number,
        required: true,
    },
    first: {
        type: Number,
        required: true,
    },
});

// Определяем emits
const emit = defineEmits(['page']);

const handlePageChange = (event) => {
    // Преобразуем индексацию с 0 на 1, так как PrimeVue использует индексацию с 0, а Laravel с 1
    const newEvent = {
        ...event,
        page: event.page + 1, // Преобразуем индексацию
    };
    emit('page', newEvent); // Эмитим событие с исправленной индексацией
};
</script>

<style scoped>
/* Убираем задний фон всего пагинатора */
.custom-paginator :deep(.p-paginator) {
    background-color: transparent;
    border: none;
    padding: 0;
}

/* Стили для активной кнопки текущей страницы */
.custom-paginator :deep(.p-paginator-page.p-paginator-page-selected) {
    background-color: #1f2937;
    color: white;
    border-color: #1f2937;
}

.custom-paginator :deep(.p-paginator-page) {
    height: 2rem;
    min-width: 2rem;
}

/* Стили для кнопок при наведении */
.custom-paginator :deep(.p-paginator-page:not(.p-paginator-page-selected):hover) {
    background-color: #e5e7eb;
    color: #1f2937;
}
@media (max-width: 768px) {
    .custom-paginator :deep(.p-paginator-page),
    .custom-paginator :deep(.p-paginator-prev),
    .custom-paginator :deep(.p-paginator-next),
    .custom-paginator :deep(.p-paginator-first),
    .custom-paginator :deep(.p-paginator-last) {
        height: 1.5rem;
        min-width: 1.5rem;
        margin: 0 2px;
    }
}
</style>
