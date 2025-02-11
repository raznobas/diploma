<script setup>
import InputError from "@/Components/InputError.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {onMounted, ref} from "vue";
import InputMask from "primevue/inputmask";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const emit = defineEmits(['submit']);
const props = defineProps({
    isLead: {
        type: Boolean,
        required: true,
    },
    initialPhone: {
        type: String,
        default: null,
    },
});

const form = useForm({
    surname: null,
    name: null,
    patronymic: null,
    birthdate: null,
    workplace: null,
    phone: props.initialPhone || null,
    email: null,
    telegram: null,
    instagram: null,
    address: null,
    gender: "male",
    ad_source: null,
    is_lead: null,
    director_id: usePage().props.auth.director_id,
});

const sourceOptions = ref(null);
const getSourceOptions = async () => {
    const responseSourceOptions = await axios.get(route('clients.getSourceOptions'));
    sourceOptions.value = responseSourceOptions.data;
};
const submitForm = () => {
    emit('submit', form);
};

onMounted(() => {
    getSourceOptions();
});
</script>

<template>
    <div class="bg-white px-4 pb-4 p-6">
        <form @submit.prevent="submitForm">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 items-end mt-3">
                <div class="flex flex-col max-sm:col-span-2">
                    <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                    <input type="text" v-model="form.surname" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.surname" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col max-sm:col-span-2">
                    <label for="name" class="text-sm font-medium text-gray-700">Имя<span class="text-red-600">*</span></label>
                    <input type="text" required v-model="form.name" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.name" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество</label>
                    <input type="text" v-model="form.patronymic" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.patronymic" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="birthdate" class="text-sm font-medium text-gray-700">Дата рождения</label>
                    <input type="date" v-model="form.birthdate" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.birthdate" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="workplace" class="text-sm font-medium text-gray-700">Место работы</label>
                    <input type="text" v-model="form.workplace" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.workplace" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                    <InputMask id="phone" v-model="form.phone" mask="+7 (999) 999-99-99" placeholder="+7" class="p-1 border border-gray-300 rounded-md" fluid />
                    <InputError :message="form.errors.phone" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="email" class="text-sm font-medium text-gray-700">Почта</label>
                    <input type="text" v-model="form.email" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.email" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="telegram" class="text-sm font-medium text-gray-700">Телеграм</label>
                    <input type="text" v-model="form.telegram" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.telegram" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="instagram" class="text-sm font-medium text-gray-700">Инстаграм</label>
                    <input type="text" v-model="form.instagram" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.instagram" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="address" class="text-sm font-medium text-gray-700">Адрес</label>
                    <input type="text" v-model="form.address" class="p-1 border border-gray-300 rounded-md"/>
                    <InputError :message="form.errors.address" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="gender" class="text-sm font-medium text-gray-700">Пол</label>
                    <select v-model="form.gender" class="p-1 border border-gray-300 rounded-md">
                        <option value="male">М</option>
                        <option value="female">Ж</option>
                    </select>
                    <InputError :message="form.errors.gender" class="mt-2 text-sm text-red-600"/>
                </div>
                <div class="flex flex-col">
                    <label for="ad_source" class="text-sm font-medium text-gray-700">Источник</label>
                    <select id="ad_source" v-model="form.ad_source" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                        <option v-for="source in sourceOptions"
                                :value="source.name" :key="source.id">{{ source.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.ad_source" class="mt-2 text-sm text-red-600"/>
                </div>
            </div>
            <div class="mt-4 flex">
                <PrimaryButton v-if="props.isLead" type="submit">Создать лид</PrimaryButton>
                <PrimaryButton v-else type="submit">Создать клиента</PrimaryButton>
                <SecondaryButton class="ml-2" type="button" @click="form.reset()">Очистить</SecondaryButton>
            </div>
        </form>
    </div>
</template>
