<script setup>
import {ref, onMounted} from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import {Link, usePage} from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const abilities = ref([]);
const wazzupUser = usePage().props.auth.wazzup_user;

onMounted(() => {
    abilities.value = usePage().props.auth.abilities || [];
});

const hasAbility = (ability) => {
    return abilities.value.includes(ability);
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800"/>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-6 lg:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Панель
                                </NavLink>
                                <NavLink :href="route('clients.index')" :active="route().current('clients.index')">
                                    Клиенты
                                </NavLink>
                                <NavLink :href="route('clients.old')" :active="route().current('clients.old')">
                                    Старые клиенты
                                </NavLink>
                                <NavLink :href="route('leads.index')" :active="route().current('leads.index')">
                                    Лиды
                                </NavLink>
                                <NavLink :href="route('sales.index')" :active="route().current('sales.index')">
                                    Продажи
                                </NavLink>
                                <NavLink :href="route('clients.trials')" :active="route().current('clients.trials')">
                                    Старые пробники
                                </NavLink>
                                <div
                                    class="relative inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button type="button"
                                                    class="inline-flex py-5 items-center border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                Задачи
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                     viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('tasks.index')"> Все задачи</DropdownLink>
                                            <DropdownLink :href="route('tasks.noShowLeads')"> Не пришедшие лиды
                                            </DropdownLink>
                                            <DropdownLink :href="route('tasks.trialsLessThanMonth')"> Пробы менее
                                                месяца
                                            </DropdownLink>
                                            <DropdownLink :href="route('tasks.renewals')"> Продление</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                <NavLink :href="route('calls.index')" :active="route().current('calls.index')">
                                    Звонки
                                </NavLink>
                                <NavLink v-if="hasAbility('manage-categories')" :href="route('categories.index')"
                                         :active="route().current('categories.index')">
                                    Настройка категорий
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden lg:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}

                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                     viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Профиль</DropdownLink>
                                        <DropdownLink v-if="$page.props.auth.role === 'director'"
                                                      :href="route('analytics.index')"> Аналитика</DropdownLink>
                                        <DropdownLink :href="route('export.index')"> Экспорт</DropdownLink>
                                        <DropdownLink v-if="wazzupUser" :href="route('whatsapp.index')"> WhatsApp</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Выйти
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center lg:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 6h16M4 12h16M4 18h16"/>
                                    <path :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                     class="lg:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Панель
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('clients.index')" :active="route().current('clients.index')">
                            Клиенты
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('clients.old')" :active="route().current('clients.old')">
                            Старые клиенты
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('leads.index')" :active="route().current('leads.index')">
                            Лиды
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('sales.index')" :active="route().current('sales.index')">
                            Продажи
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('clients.trials')" :active="route().current('clients.trials')">
                            Старые пробники
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('tasks.index')"
                                           :active="route().current('tasks.index')"> Все задачи
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('tasks.noShowLeads')"
                                           :active="route().current('tasks.noShowLeads')"> Не пришедшие лиды
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('tasks.trialsLessThanMonth')"
                                           :active="route().current('tasks.trialsLessThanMonth')"> Пробы менее месяца
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('tasks.renewals')"
                                           :active="route().current('tasks.renewals')"> Продление
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('calls.index')"
                                           :active="route().current('calls.index')"> Звонки
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="hasAbility('manage-categories')" :href="route('categories.index')"
                                           :active="route().current('categories.index')">
                            Настройка категорий
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Профиль</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.auth.role === 'director'"
                                               :href="route('analytics.index')"> Аналитика</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('export.index')"> Экспорт</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('whatsapp.index')"> WhatsApp</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Выйти
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header"/>
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot/>
            </main>
        </div>
    </div>
</template>
