<script setup>
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import VueMultiselect from "vue-multiselect";
import {reactive, ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import {useToast} from "@/useToast";
const {showToast} = useToast();

const props = defineProps({
    show: Boolean,
    sale: Object,
    categories: Object,
    categoryCosts: Object,
});

const emit = defineEmits(['update', 'close']);

const form = useForm({
    sale_date: props?.sale?.sale_date,
    client_id: props?.sale?.client_id,
    director_id: props?.sale?.director_id,
    service_or_product: props?.sale?.service_or_product,
    sport_type: props?.sale?.sport_type,
    service_type: props?.sale?.service_type,
    subscription_duration: props?.sale?.subscription_duration,
    visits_per_week: props?.sale?.visits_per_week,
    training_count: props?.sale?.training_count,
    trainer_category: props?.sale?.trainer_category,
    trainer: props?.sale?.trainer,
    product_type: props?.sale?.product_type,
    subscription_start_date: props?.sale?.subscription_start_date,
    subscription_end_date: props?.sale?.subscription_end_date,
    cost: props?.sale?.cost,
    paid_amount: props?.sale?.paid_amount,
    pay_method: props?.sale?.pay_method,
    comment: props?.sale?.comment,
    created_by: props?.sale?.created_by,
});
watch(() => props.sale, newSale => {
    if (newSale) {
        Object.assign(form, JSON.parse(JSON.stringify(newSale)));
        client_object.id = newSale.client_id;
        client_object.name = newSale.client.name;
        client_object.surname = newSale.client.surname;
        client_object.patronymic = newSale.client.patronymic;
        client_object.is_lead = newSale.client.is_lead;

        initializeForm();
    }
}, {deep: true});

let client_object = reactive({});

const onClientSelect = (selectedOption) => {
    form.client_id = selectedOption.id;
};

const initializeForm = () => {
    const findCategoryId = (value, type) => {
        const parsedValue = !isNaN(parseFloat(value)) ? parseFloat(value) : value;
        const category = props.categories.find(c => {
            const parsedCategoryValue = !isNaN(parseFloat(c.name)) ? parseFloat(c.name) : c.name;
            return parsedCategoryValue === parsedValue && c.type === type;
        });
        return category ? category.id : null;
    };

    form.sport_type = findCategoryId(form.sport_type, 'sport_type');
    form.product_type = findCategoryId(form.product_type, 'product_type');
    form.subscription_duration = findCategoryId(form.subscription_duration, 'subscription_duration');
    form.visits_per_week = findCategoryId(form.visits_per_week, 'visits_per_week');
    form.training_count = findCategoryId(form.training_count, 'training_count');
    form.trainer_category = findCategoryId(form.trainer_category, 'trainer_category');
    form.trainer = findCategoryId(form.trainer, 'trainer');
    form.pay_method = findCategoryId(form.pay_method, 'pay_method');
};

const updateFormWithNames = () => {
    const findCategoryNameById = (id, type) => {
        const category = props.categories.find(c => c.id === id && c.type === type);
        return category ? category.name : null;
    };

    form.sport_type = findCategoryNameById(form.sport_type, 'sport_type');
    form.product_type = findCategoryNameById(form.product_type, 'product_type');
    form.subscription_duration = findCategoryNameById(form.subscription_duration, 'subscription_duration');
    form.visits_per_week = findCategoryNameById(form.visits_per_week, 'visits_per_week');
    form.training_count = findCategoryNameById(form.training_count, 'training_count');
    form.trainer_category = findCategoryNameById(form.trainer_category, 'trainer_category');
    form.trainer = findCategoryNameById(form.trainer, 'trainer');
    form.pay_method = findCategoryNameById(form.pay_method, 'pay_method');
};

const submitForm = () => {
    updateFormWithNames();
    emit('update', form);
};

const categoryMap = props.categories.reduce((map, category) => {
    map[category.id] = category.name;
    return map;
}, {});

const calculateEndDate = () => {
    if (form.subscription_start_date && form.subscription_duration) {
        const startDate = new Date(form.subscription_start_date);
        let endDate;
        const durationName = categoryMap[form.subscription_duration];

        // Обработка основных вариантов длительности
        switch (durationName) {
            case '0.03': // значение для разовой
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate());
                break;
            case '0.5': // две недели
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 14);
                break;
            case '1':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 30);
                break;
            case '3':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 92);
                break;
            case '6':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 182);
                break;
            case '12':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 365);
                break;
            default:
                endDate = null;
                break;
        }

        if (endDate) {
            form.subscription_end_date = endDate.toISOString().split('T')[0];
        }
    } else if (form.subscription_start_date && form.training_count && (categoryMap[form.training_count] === 'Блок 8 тренировок' || categoryMap[form.training_count] === 'Блок 20 тренировок')) {
        // Обработка случая только с "Кол-во тренировок"
        const startDate = new Date(form.subscription_start_date);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 182);
        form.subscription_end_date = endDate.toISOString().split('T')[0];
    }
};
watch(() => [
    form.subscription_start_date,
    form.subscription_duration,
    form.training_count,
], calculateEndDate);

// поиск покупателя/лида
const search = ref([]);

const searchClients = async (query) => {
    if (query.length > 2) {
        try {
            const url = route('clients.search', {query});
            const response = await axios.get(url);
            search.value = response.data;
        } catch (error) {
            showToast("Ошибка поиска: " + error.message, "error");
        }
    } else {
        search.value = [];
    }
};

const fullName = (option) => {
    const parts = [];
    if (option.is_lead) parts.push('(Л)');
    else parts.push('(К)');
    if (option.surname) parts.push(option.surname);
    if (option.name) parts.push(option.name);
    if (option.patronymic) parts.push(option.patronymic);
    return parts.join(' ');
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div v-if="sale" class="bg-white px-4 pb-4 sm:p-6 sm:pb-4">
            <form @submit.prevent="submitForm">
                <div class="flex flex-row flex-wrap gap-2 auto-cols-max items-end mt-2">
                    <div class="flex flex-col col-span-1 w-32">
                        <label for="edit_sale_date" class="text-sm font-medium text-gray-700">Дата продажи</label>
                        <input id="edit_sale_date" type="date" v-model="form.sale_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.sale_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-56 relative">
                        <label for="edit_fio" class="text-sm font-medium text-gray-700">Имя</label>
                        <vue-multiselect
                            id="edit_fio"
                            v-model="client_object"
                            :options="search"
                            :searchable="true"
                            :max-height="400"
                            :options-limit="200"
                            :placeholder="'Поиск'"
                            :show-labels="false"
                            :custom-label="fullName"
                            :internal-search="false"
                            :allow-empty="false"
                            track-by="id"
                            @search-change="searchClients"
                            @select="onClientSelect"
                        >
                            <template v-slot:option="props">
                                {{ fullName(props.option) }}
                            </template>
                        </vue-multiselect>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_service_or_product" class="text-sm font-medium text-gray-700">Услуга/Товар</label>
                        <select id="edit_service_or_product" required v-model="form.service_or_product"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option value="service">Услуга</option>
                            <option value="product">Товар</option>
                        </select>
                        <InputError :message="form.errors.service_or_product" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_sport_type" class="text-sm font-medium text-gray-700">Вид спорта</label>
                        <select id="edit_sport_type" v-model="form.sport_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'sport_type')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.sport_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_service_type" class="text-sm font-medium text-gray-700">Вид услуги</label>
                        <select id="edit_service_type" v-model="form.service_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option value="trial">Пробная</option>
                            <option value="group">Групповая</option>
                            <option value="minigroup">Минигруппа</option>
                            <option value="individual">Индивидуальная</option>
                            <option value="split">Сплит</option>
                        </select>
                        <InputError :message="form.errors.service_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_product_types" class="text-sm font-medium text-gray-700">Вид товара</label>
                        <select id="edit_product_types" v-model="form.product_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'product_type')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.product_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="edit_subscription_duration"
                               class="text-sm font-medium text-gray-700">Длительность абонемента</label>
                        <select id="edit_subscription_duration" v-model="form.subscription_duration"
                                @change="calculateEndDate"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'subscription_duration')"
                                    :value="category.id" :key="category.id">
                                {{ category.name === '0.03' ? 'Разовая' : category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subscription_duration" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="edit_visits_per_week" class="text-sm font-medium text-gray-700">Посещений в
                            неделю</label>
                        <select id="edit_visits_per_week" v-model="form.visits_per_week"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'visits_per_week')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.visits_per_week" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_training_count" class="text-sm font-medium text-gray-700">Кол-во тренировок</label>
                        <select id="edit_training_count" v-model="form.training_count"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'training_count')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.training_count" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_trainer_category" class="text-sm font-medium text-gray-700">Категория
                            тренера</label>
                        <select id="edit_trainer_category" v-model="form.trainer_category"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'trainer_category')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer_category" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_trainer" class="text-sm font-medium text-gray-700">Тренер</label>
                        <select id="edit_trainer" v-model="form.trainer"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'trainer')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer_category" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                    >
                        <label for="edit_subscription_start_date" class="text-sm font-medium text-gray-700">Начало
                            абонемента</label>
                        <input id="edit_subscription_start_date" type="date" v-model="form.subscription_start_date"
                               @change="calculateEndDate"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.subscription_start_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                    >
                        <label for="edit_subscription_end_date" class="text-sm font-medium text-gray-700">Окончание
                            абонемента</label>
                        <input id="edit_subscription_end_date" type="date" v-model="form.subscription_end_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.subscription_end_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-24">
                        <label for="edit_cost" class="text-sm font-medium text-gray-700">Стоимость</label>
                        <input id="edit_cost" type="number" min="0" step="1" v-model="form.cost"
                               class="mt-1 p-1 border border-gray-300 rounded-md" required/>
                        <InputError :message="form.errors.cost" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-24">
                        <label for="edit_paid_amount" class="text-sm font-medium text-gray-700">Сумма оплач.</label>
                        <input id="edit_paid_amount" type="number" min="0" step="1" v-model="form.paid_amount"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.paid_amount" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="edit_pay_method" class="text-sm font-medium text-gray-700">Способ оплаты</label>
                        <select id="edit_pay_method" v-model="form.pay_method"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'pay_method')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.paid_amount" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="edit_comment" class="text-sm font-medium text-gray-700">Комментарий</label>
                        <input id="edit_comment" type="text" v-model="form.comment"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.comment" class="mt-2 text-sm text-red-600"/>
                    </div>
                </div>
                <div class="mt-4">
                    <PrimaryButton :disabled="form.processing">Обновить продажу</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
