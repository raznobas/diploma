<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {useForm, Head, usePage} from '@inertiajs/vue3';
import {useToast} from "@/useToast";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const {showToast} = useToast();

const props = defineProps(['categories', 'categoryCosts']);

const form = useForm({
    name: "",
    type: "",
    director_id: usePage().props.auth.director_id,
});
const form_costs = useForm({
    mainCategory: {type: "", option: ""},
    additionalCategories: [],
    cost: "",
    director_id: usePage().props.auth.director_id,
});

const addSecondCategory = () => {
    if (form_costs.additionalCategories.length < types.length - 1) {
        form_costs.additionalCategories.push({type: "", option: ""});
    }
};

const removeSecondCategory = (index) => {
    form_costs.additionalCategories.splice(index, 1);
};

const types = [
    {name: "sport_type", title: 'Виды спорта'},
    {name: "product_type", title: 'Виды товаров'},
    {name: "training_count", title: 'Кол-во тренировок'},
    {name: "subscription_duration", title: 'Длительность абонементов'},
    {name: "visits_per_week", title: 'Кол-во посещений в неделю'},
    {name: "trainer", title: 'Тренеры'},
    {name: "trainer_category", title: 'Категории тренеров'},
    {name: "pay_method", title: 'Способы оплаты'},
    {name: "ad_source", title: 'Источники лидов'},
];

const submit = () => {
    if (form.type.name === 'visits_per_week') {
        if (isNaN(form.name) || form.name === '') {
            alert('Пожалуйста, введите число для кол-ва посещений в неделю');
            return;
        }
    } else if (form.type.name === 'subscription_duration') {
        if (isNaN(form.name) || form.name === '') {
            alert('Пожалуйста, введите значение в месяцах для длительности абонемента. Если ваша длительность в неделях - укажите дробное число, используя "точку"');
            return;
        }
    }

    form.post(route('categories.store'), {
        onSuccess: () => {
            form.name = '';
            showToast("Опция категории успешно добавлена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
const submitCost = (costId = null) => {
    const routeName = costId ? 'categoriesCost.update' : 'categoriesCost.store';
    const routeParams = costId ? {categoriesCost: costId} : {};

    const method = costId ? 'put' : 'post';

    form_costs.submit(method, route(routeName, routeParams), {
        onSuccess: () => {
            resetCostForm();
            showToast("Стоимость категории успешно сохранена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

const deleteOptionCategory = (category) => {
    if (confirm('Вы уверены, что хотите удалить эту опцию категории?')) {
        form.delete(route('categories.destroy', category.id), {
            onSuccess: () => {
                showToast("Опция категории успешно удалена!", "success");
            },
        });
    }
};
const deleteCost = (costId) => {
    if (confirm('Вы уверены, что хотите удалить эту сборку стоимости?')) {
        form.delete(route('categoriesCost.destroy', costId), {
            onSuccess: () => {
                showToast("Сборка стоимости успешно удалена!", "success");
            },
        });
    }
};

const getCategoryType = (categoryId) => {
    const category = props.categories.find(c => c.id === categoryId);
    if (!category) return 'Неизвестная категория';
    const type = types.find(t => t.name === category.type);
    return type ? type.title : 'Неизвестная категория';
};
const getCategoryName = (categoryId) => {
    const category = props.categories.find(c => c.id === categoryId);
    return category ? category.name : 'Неизвестно';
};

// редактирование категорий
const editingCategory = ref(null);
const editModal = ref(false);
const editCategory = (category) => {
    editModal.value = true;
    editingCategory.value = JSON.parse(JSON.stringify(category));
};
const cancelEdit = () => {
    editModal.value = false;
    editingCategory.value = null;
};
const submitEditOption = () => {
    if (editingCategory.value.name.trim() === '') {
        alert('Название категории не может быть пустым');
        return;
    }

    form.name = editingCategory.value.name;
    form.put(route('categories.update', editingCategory.value.id), {
        onSuccess: () => {
            cancelEdit();
            form.reset();
            showToast("Категория успешно обновлена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

// редактирование стоимости категорий
const editingCostId = ref(null);
const resetCostForm = () => {
    form_costs.reset();
    editingCostId.value = null;
};
const fillCostForm = (cost) => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    const mainCategory = props.categories.find(c => c.id === cost.main_category_id);
    if (mainCategory) {
        form_costs.mainCategory.type = mainCategory.type;
        form_costs.mainCategory.option = mainCategory.name;
    } else {
        form_costs.mainCategory.type = '';
        form_costs.mainCategory.option = '';
    }

    form_costs.cost = cost.cost || '';

    if (cost.additional_costs) {
        form_costs.additionalCategories = cost.additional_costs.map(additional => {
            const additionalCategory = props.categories.find(c => c.id === additional.additional_category_id);
            return {
                type: additionalCategory ? additionalCategory.type : '',
                option: additionalCategory ? additionalCategory.name : ''
            };
        });
    } else {
        form_costs.additionalCategories = [];
    }

    editingCostId.value = cost.id;
};

</script>

<template>
    <Head title="Настройка категорий"/>

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto my-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Modal :max-width="'lg'" :show="editModal" @close="cancelEdit">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <form @submit.prevent="submitEditOption" class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg mb-3 leading-6 font-medium text-gray-900" id="modal-title">
                                <span>Редактирование категории</span>
                            </h3>
                            <div class="text-sm font-medium text-gray-700">{{
                                    getCategoryType(editingCategory.id)
                                }}
                            </div>
                            <input v-model="editingCategory.name" type="text" class="mt-1 block" required/>
                            <div class="mt-4 flex justify-start">
                                <primary-button type="submit">Редактировать</primary-button>
                                <secondary-button class="ms-2" type="button" @click="cancelEdit">Отменить
                                </secondary-button>
                            </div>
                        </form>
                    </div>
                </Modal>
                <!-- Форма добавления новых категорий -->
                <form @submit.prevent="submit" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">Добавление новых категорий</h2>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Название</label>
                            <input v-model="form.name" type="text" id="name" class="mt-1 block w-full" required/>
                            <InputError :message="form.errors.name" class="mt-2"/>
                        </div>
                        <div class="mb-4">
                            <label for="type" class="block text-sm font-medium text-gray-700">Тип</label>
                            <select v-model="form.type" id="type" class="mt-1 block w-full" required>
                                <option v-for="type in types" :key="type.name" :value="type.name">{{
                                        type.title
                                    }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-2"/>
                        </div>
                        <PrimaryButton>Добавить</PrimaryButton>
                    </div>
                </form>

                <!-- Форма настройки стоимости категорий -->
                <form @submit.prevent="submitCost(editingCostId)"
                      class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">Настройка стоимости категорий</h2>
                        <!-- Основная категория -->
                        <div class="mb-2">
                            <label for="mainCategory" class="block text-sm font-medium text-gray-700">Категория
                                основная</label>
                            <select v-model="form_costs.mainCategory.type" id="mainCategory" class="mt-1 block w-full"
                                    required>
                                <option v-for="type in types.filter(t => t.name !== 'ad_source')" :key="type.name"
                                        :value="type.name">{{ type.title }}
                                </option>
                            </select>
                            <select v-model="form_costs.mainCategory.option" class="mt-1 block w-full" required>
                                <option v-for="cat in categories.filter(c => c.type === form_costs.mainCategory.type)"
                                        :key="cat.id" :value="cat.name">{{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Дополнительные категории -->
                        <div v-for="(category, index) in form_costs.additionalCategories" :key="index"
                             class="mt-4 mb-1">
                            <label :for="`additionalCategory-${index}`" class="block text-sm font-medium text-gray-700">Категория
                                дополнительная</label>
                            <select v-model="category.type" :id="`additionalCategory-${index}`"
                                    class="mt-1 block w-full" required>
                                <option v-for="type in types.filter(t => t.name !== 'ad_source')" :key="type.name"
                                        :value="type.name">{{ type.title }}
                                </option>
                            </select>
                            <select v-model="category.option" class="mt-1 block w-full" required>
                                <option v-for="cat in categories.filter(c => c.type === category.type)" :key="cat.id"
                                        :value="cat.name">{{ cat.name }}
                                </option>
                            </select>
                            <button type="button" @click="removeSecondCategory(index)"
                                    class="mt-1 text-red-600 text-sm hover:text-red-800">Убрать дополнительную категорию
                            </button>
                        </div>
                        <button type="button" @click="addSecondCategory"
                                v-if="form_costs.additionalCategories.length < types.length - 1">+ дополнительная
                            категория
                        </button>

                        <!-- Ввод стоимости -->
                        <div class="mb-4 mt-3">
                            <label for="cost" class="block text-sm font-medium text-gray-700">Стоимость</label>
                            <input v-model="form_costs.cost" type="number" min="0" step="1" id="cost"
                                   class="mt-1 p-0.5 w-1/2" required/> &#8381;
                            <InputError :message="form_costs.errors.cost" class="mt-2"/>
                        </div>
                        <div class="flex gap-2" v-if="editingCostId">
                            <PrimaryButton>Сохранить</PrimaryButton>
                            <SecondaryButton @click="resetCostForm">Отмена</SecondaryButton>
                        </div>
                        <PrimaryButton v-else>Добавить</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
        <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg divide-y mb-6">
            <div v-for="(cost, index) in categoryCosts" :key="cost.id" class="px-4 py-2">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">Связка стоимости #{{ index + 1 }}</h2>
                    <div>
                        <button @click="fillCostForm(cost)" class="text-blue-600 text-sm hover:text-blue-800 mr-2">
                            Редактировать
                        </button>
                        <button @click="deleteCost(cost.id)" class="text-red-600 text-sm hover:text-red-800">Удалить
                        </button>
                    </div>
                </div>
                <div class="mt-1 text-sm text-gray-500">
                    <div class="flex justify-between items-center">
                        <span>Основная категория:</span>
                        <span>{{ getCategoryType(cost.main_category_id) }} = {{
                                getCategoryName(cost.main_category_id)
                            }}</span>
                    </div>
                    <div v-if="cost.additional_costs && cost.additional_costs.length > 0"
                         v-for="additional in cost.additional_costs" :key="additional.id"
                         class="flex justify-between items-center">
                        <span>Дополнительная категория:</span>
                        <span>{{
                                getCategoryType(additional.additional_category_id)
                            }} = {{ getCategoryName(additional.additional_category_id) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Стоимость:</span>
                        <span>{{ cost.cost }} &#8381;</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg divide-y">
            <div v-for="type in types" :key="type.name" class="px-4 py-2">
                <h2 class="text-lg font-medium text-gray-900">{{ type.title }}</h2>
                <ul class="mt-1 text-sm max-sm:text-xs text-gray-500">
                    <li v-for="category in categories.filter(c => c.type === type.name)" :key="category.id"
                        class="flex justify-between items-center">
                        {{ category.name }}
                        <div>
                            <button @click="editCategory(category)"
                                    class="ml-2 text-blue-600 text-sm hover:text-blue-800">Редактировать
                            </button>
                            <button @click="deleteOptionCategory(category)"
                                    class="ml-2 text-red-600 text-sm hover:text-red-800">Удалить
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input[type=text], select {
    padding: 3px 6px;
}
</style>
