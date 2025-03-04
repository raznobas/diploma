<script setup>

import dayjs from "dayjs";
import 'dayjs/locale/ru';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head} from "@inertiajs/vue3";
import Tooltip from "@/Components/Tooltip.vue";
import {computed} from "vue";

const props = defineProps(['analytics']);

// Группировка данных по уровню агрегации в один объект
const groupedAnalytics = computed(() => {
    return [
        { label: 'Месячный отчет', level: 'monthly', data: props.analytics.filter(item => item.aggregation_level === 'monthly') },
        { label: 'Квартальный отчет', level: 'quarterly', data: props.analytics.filter(item => item.aggregation_level === 'quarterly') },
        { label: 'Годовой отчет', level: 'yearly', data: props.analytics.filter(item => item.aggregation_level === 'yearly') }
    ];
});

// Функция для форматирования процентов
const formatPercentage = (value) => {
    const number = parseFloat(value);
    if (isNaN(number)) return '0'; // Проверка на NaN
    return number % 1 === 0 ? number.toString() : number.toFixed(2).replace(/\.?0+$/, '');
};


// Функция для формирования текста периода, в зависимости от уровня агрегации
const formatPeriod = (item) => {
    if(item.aggregation_level === 'monthly' && item.month) {
        return dayjs(item.month).locale('ru').format('MMMM YYYY');
    } else if(item.aggregation_level === 'quarterly' && item.quarter) {
        return `${item.year} г., ${item.quarter} кв.`;
    } else if(item.aggregation_level === 'yearly' && item.year) {
        return `${item.year} год`;
    }
    return '';
}
</script>

<template>
    <Head title="Аналитика"/>

    <AuthenticatedLayout>
        <template #header>
            <h2>Аналитика</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs text-sm">
            <div v-for="group in groupedAnalytics" :key="group.level" class="mb-8">
                <h3 class="mb-3 text-lg font-medium text-gray-900 text-center">{{ group.label }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Период</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Лиды
                                    <Tooltip class="ms-1" content="Лиды – люди из формы обратной связи и те, кто был добавлен менеджерами вручную." />
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Все <br> лиды
                                    <Tooltip class="ms-1" content="Все лиды – люди из формы обратной связи, люди, добавленные вручную, а также те, кто либо прошли пробную тренировку, либо совершили первую покупку услуг в рамках текущего периода, минуя пробную тренировку. Покупка товаров не учитывается." />
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Пробники
                                    <Tooltip class="ms-1" content="Пробники – это люди, которые прошли пробную тренировку, но не совершили какую-либо покупку услуг."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Все <br> пробники
                                    <Tooltip class="ms-1" content="Все пробники – это люди, которые либо прошли пробную тренировку, либо совершили первую покупку услуг в рамках текущего периода, минуя пробную тренировку. Покупка товаров не учитывается. Процентное соотношение относительно всех лидов."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Пробники <br> из лидов
                                    <Tooltip class="ms-1" content="Пробники из таблицы лидов - это количество клиентов, которые прошли через статус лида и затем приобрели любую услугу. Покупка товаров не учитывается. Процентное соотношение относительно лидов."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Первая <br> покупка
                                    <Tooltip class="ms-1" content="Первая покупка – это любая оплата в рамках текущего периода, кроме пробной тренировки. Покупка товаров не учитывается. Процентное соотношение относительно всех лидов."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Уникал. <br> клиенты
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продажи</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Продления
                                    <Tooltip class="ms-1" content="Продления определяются как кол-во уникальных клиентов, совершивших продление абонемента в рамках текущего периода. Учитываются только групповые абонементы."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center whitespace-nowrap">
                                    Повтор. <br> продления
                                    <Tooltip class="ms-1" content="Повторные продления определяются как кол-во уникальных клиентов, которые приобрели абонемент минимум второй раз и более. Учитывается любой тип услуги, кроме пробной. Процентное соотношение относительно уникальных клиентов."/>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма продаж</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Средний чек</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Перс. продажи</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма перс. продаж</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прочие услуги</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in group.data" :key="item.year + '-' + (item.month || item.quarter)">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ formatPeriod(item) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ item.leads }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ Number(item.leads) + Number(item.purchase_trials) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.trials }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.purchase_trials }} ({{ formatPercentage((item.purchase_trials / (Number(item.leads) + Number(item.purchase_trials))) * 100) }}%)
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.leads_to_sales }} ({{ formatPercentage((Number(item.leads_to_sales) / (Number(item.leads))) * 100) }}%)
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.first_purchases }} ({{ formatPercentage((item.first_purchases / (Number(item.leads) + Number(item.purchase_trials))) * 100) }}%)
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ item.unique_clients }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ item.total_sales }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ item.renewals }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.repeat_renewals }} ({{ formatPercentage((item.repeat_renewals / (Number(item.unique_clients))) * 100) }}%)
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap font-black">{{ Number(item.total_paid_amount).toFixed(0) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ Number(item.avg_check).toFixed(0) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ item.individual_sales_count }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.individual_sales_total }} ({{ formatPercentage((item.individual_sales_total / item.total_paid_amount) * 100) }}%)
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ item.products_total }} ({{ formatPercentage((item.products_total / item.total_paid_amount) * 100) }}%)
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
