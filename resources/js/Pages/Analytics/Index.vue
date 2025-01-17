<script setup>

import dayjs from "dayjs";
import 'dayjs/locale/ru';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head} from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps(['analytics']);

</script>

<template>
    <Head title="Аналитика"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Аналитика</h2>
        </template>
        <div class="mx-auto p-4 sm:p-6 lg:p-8 max-sm:text-xs">
            <div class="max-lg:overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Период</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Пробники</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Первая покупка</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продажи</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продления</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма продаж</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Средний чек</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Перс. продажи</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма перс. продаж</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прочие услуги</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in analytics" :key="item.month || item.quarter || item.year">
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
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.trials }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.first_purchases }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.total_sales }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.renewals }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.total_paid_amount }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.avg_check }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.individual_sales_count }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.individual_sales_total }} ({{ item.individual_sales_percentage }})</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ item.products_total }} ({{ item.products_percentage }})</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
