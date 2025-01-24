<script setup>

import dayjs from "dayjs";
import 'dayjs/locale/ru';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head} from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import {computed} from "vue";
import Tooltip from "@/Components/Tooltip.vue";

const props = defineProps(['analytics']);

// Функция, которая убирает знаки после запятой, если после запятой нули
const formatPercentage = (value) => {
    const number = parseFloat(value);
    return number % 1 === 0 ? number.toString() : number.toFixed(2).replace(/\.?0+$/, '');
}
</script>

<template>
    <Head title="Аналитика"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Аналитика</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Период</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex items-center whitespace-nowrap">
                                Лиды
                                <Tooltip class="ms-1" content="Лиды – люди из формы обратной связи, люди, добавленные вручную, а также те, кто либо прошли пробную тренировку, либо совершили первую покупку услуг в рамках текущего периода, минуя пробную тренировку. Покупка товаров не учитывается." />
                            </div>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex items-center whitespace-nowrap">
                                Пробники
                                <Tooltip class="ms-1" content="Пробники – это люди, которые либо прошли пробную тренировку, либо совершили первую покупку услуг в рамках текущего периода, минуя пробную тренировку. Покупка товаров не учитывается. Процентное соотношение относительно лидов."/>
                            </div>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex items-center whitespace-nowrap">
                                Первая <br> покупка
                                <Tooltip class="ms-1" content="Первая покупка – это любая оплата в рамках текущего периода, кроме пробной тренировки. Покупка товаров не учитывается. Процентное соотношение относительно лидов."/>
                            </div>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продажи</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продления</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Звонки</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Заявки</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма продаж</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Средний чек</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Перс. продажи</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма перс. продаж</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прочие услуги</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in analytics" :key="item.month || item.quarter || item.year"
                        :class="
                            item.month ? 'bg-white' :
                            item.quarter ? 'bg-blue-100' :
                            item.year && !item.quarter && !item.month ? 'bg-green-200' : ''
                        ">
                        <td class="px-3 py-2 whitespace-nowrap">
                            <!-- Отображение месяца, квартала или года -->
                            <span v-if="item.month">
                                    {{ dayjs(item.month).locale('ru').format('MMMM YYYY') }}
                            </span>
                            <span v-else-if="item.quarter">
                                    {{ item.year }} год, {{ item.quarter }} квартал
                            </span>
                            <span v-else-if="item.year">
                                    Итог за {{ item.year }} год
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ Number(item.leads) + Number(item.trials) }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ item.trials }} ({{ formatPercentage((item.trials / (Number(item.leads) + Number(item.trials))) * 100) }}%)
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ item.first_purchases }} ({{ formatPercentage((item.first_purchases / (Number(item.leads) + Number(item.trials))) * 100) }}%)
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.total_sales }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.renewals }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.calls }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.form_leads }}</td>
                        <td class="px-3 py-2 whitespace-nowrap font-black">
                            {{ Number(item.total_paid_amount).toFixed(0) }}
                        </td>
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
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
