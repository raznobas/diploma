<script setup>
import {defineProps, defineEmits, ref, watch, computed} from 'vue';
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';
import dayjs from "dayjs";
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "@/useToast";
import InputMask from "primevue/inputmask";
import Spinner from "@/Components/Spinner.vue";

const {showToast} = useToast();

const props = defineProps({
    show: Boolean,
    client: Object,
});

const isTasksLoading = ref(false);
const isClientSalesLoading = ref(false);
const iframeUrl = ref(null);
const showIframe = ref(false);
const wazzupUser = usePage().props.auth.wazzup_user;

const emit = defineEmits(['close', 'client-updated']);
const formEdit = useForm({
    id: props?.client?.id,
    surname: props?.client?.surname,
    name: props?.client?.name,
    patronymic: props?.client?.patronymic,
    birthdate: props?.client?.birthdate,
    workplace: props?.client?.workplace,
    phone: props?.client?.phone,
    email: props?.client?.email,
    telegram: props?.client?.telegram,
    instagram: props?.client?.instagram,
    address: props?.client?.address,
    gender: props?.client?.gender,
    ad_source: props?.client?.ad_source,
});
const formTask = useForm({
    client_id: null,
    director_id: usePage().props.auth.director_id,
    user_sender_id: usePage().props.auth.user.id,
    task_description: null,
    task_date: null,
});
watch(() => props.client, newClient => {
    if (newClient) {
        Object.assign(formEdit, JSON.parse(JSON.stringify(newClient)));
        loadClientSales(newClient.id);
        fetchTasks(newClient.id);
    }
}, {deep: true});

// получения списка всех покупок клиента
const clientSales = ref([]);
const totalSalesByClient = ref(0);
const loadClientSales = async (clientId) => {
    isClientSalesLoading.value = true;
    try {
        const response = await axios.get(route('sales.show', clientId));
        clientSales.value = response.data;
        calculateTotalSales();
    } catch (error) {
        showToast("Ошибка получения покупок клиента: " + error.message, "error");
    }
    isClientSalesLoading.value = false;
};

const firstSaleDate = computed(() => {
    return clientSales.value.length > 0 ? dayjs(clientSales.value[0].sale_date).format('DD.MM.YYYY') : '–';
});

// Вычисляемое свойство для общей суммы продаж
const calculateTotalSales = () => {
    totalSalesByClient.value = clientSales.value.reduce((total, sale) => {
        const paidAmount = parseFloat(sale.paid_amount);
        if (!isNaN(paidAmount)) {
            return total + paidAmount;
        }
        return total;
    }, 0);
};

const submit = () => {
    formTask.client_id = props.client.id;
    formTask.post(route('tasks.store'), {
        onSuccess: () => {
            formTask.reset();
            isEditing.value = false;
            fetchTasks(props.client.id);
            showToast("Задача успешно добавлена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
const isEditing = ref(false);
const sourceOptions = ref(null);
const editClient = async () => {
    try {
        isEditing.value = !isEditing.value;
        const responseSourceOptions = await axios.get(route('clients.getSourceOptions'));
        sourceOptions.value = responseSourceOptions.data;
    } catch (error) {
        showToast("Ошибка при получении опций источников: " + error.message, "error");
    }
};

const submitEdit = () => {
    if (!props.client || !props.client.id) {
        return;
    }

    formEdit.put(route('clients.update', {id: props.client.id}), {
        onSuccess: (response) => {
            if (response.props.error === 'DUPLICATE_PHONE_NUMBER') {
                showToast('Клиент или лид с таким номером телефона уже существует.', "error");
            } else {
                isEditing.value = false;
                emit('client-updated', formEdit.data());
                showToast("Данные успешно обновлены!", "success");
            }
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

const deleteClient = async () => {
    if (!props.client || !props.client.id) {
        return;
    }
    if (confirm('Вы уверены, что хотите удалить этого клиента/лида? Все продажи и задачи текущего клиента будут безвозвратно удалены')) {
        formEdit.delete(route('clients.destroy', {id: props.client.id}), {
            onSuccess: () => {
                showToast("Клиент/лид успешно удален!", "success");
                closeModal();
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};

const closeModal = () => {
    emit('close');
    isEditing.value = false;
    iframeUrl.value = null;
    tasks.value = [];
    clientSales.value = [];
};

// запрос на получение всех задач текущего клиента
const tasks = ref([]);
const fetchTasks = async (clientId) => {
    isTasksLoading.value = true;
    try {
        const response = await axios.get(route('tasks.show', clientId));
        tasks.value = response.data;
    } catch (error) {
        showToast("Ошибка получения задач клиента: " + error.message, "error");
    }
    isTasksLoading.value = false;
};

// Копирование данных о клиенте в буфер обмена
const copyClientInfo = () => {
    const client = props.client;
    const fields = [
        { label: 'ID', value: client.id },
        { label: 'Фамилия', value: client.surname },
        { label: 'Имя', value: client.name },
        { label: 'Отчество', value: client.patronymic },
        { label: 'Дата рождения', value: client.birthdate ? dayjs(client.birthdate).format('DD.MM.YYYY') : '' },
        { label: 'Место работы', value: client.workplace },
        { label: 'Телефон', value: client.phone },
        { label: 'Почта', value: client.email },
        { label: 'Телеграм', value: client.telegram },
        { label: 'Инстаграм', value: client.instagram },
        { label: 'Адрес', value: client.address },
        { label: 'Пол', value: client.gender === 'male' ? 'M' : client.gender === 'female' ? 'Ж' : '' },
        { label: 'Источник', value: client.ad_source },
    ];

    const clientInfo = fields
        .filter(field => field.value) // Убираем пустые значения
        .map(field => `${field.label}: ${field.value}`) // Формируем строку для каждого непустого поля
        .join('\n'); // Объединяем в текст через перенос строки

    navigator.clipboard.writeText(clientInfo)
        .then(() => {
            showToast("Информация о клиенте скопирована в буфер обмена!", "success");
        })
        .catch(() => {
            showToast("Не удалось скопировать информацию о клиенте", "error");
        });
};

const fetchIframeUrl = async () => {
    try {
        // Получаем номер телефона из props.client.phone
        let phone = props.client.phone;

        // Убираем все нецифровые символы
        phone = phone.replace(/\D/g, '');

        // Если номер начинается с 8, заменяем на 7
        if (phone.startsWith('8')) {
            phone = '7' + phone.slice(1);
        }

        // Проверяем, что номер состоит из 11 цифр и начинается с 7
        if (!/^7\d{10}$/.test(phone)) {
            showToast("Номер телефона должен начинаться с 7 и состоять из 11 цифр", "error");
        }

        // Данные, которые отправляются на бэкенд
        const data = {
            user: {
                id: wazzupUser.id,
                name: wazzupUser.name,
            },
            scope: 'card',
            filter: [
                {
                    chatType: 'whatsapp',
                    chatId: phone,
                },
            ],
            activeChat: {
                chatType: 'whatsapp',
                chatId: phone
            },
        };

        // Отправляем POST-запрос на бэкенд
        const response = await axios.post(route('whatsapp.getIframeUrl'), data);
        iframeUrl.value = response.data.url;
        showIframe.value = true;
    } catch (error) {
        showToast("Произошла ошибка", "error");
        console.error('Ошибка при получении iframe URL:', error);
    }
};

</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div v-if="client" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div>
                <div class="sm:mt-0 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mr-2 md:inline" id="modal-title">
                        <span v-if="client.is_lead === 1">Информация о лид</span>
                        <span v-else>Информация о клиенте</span>
                    </h3>
                    <div class="md:inline">
                        <button title="Копировать данные" type="button" @click="copyClientInfo" class="px-2 max-sm:py-1">
                            <i class="fa fa-files-o text-xl text-blue-600" aria-hidden="true"></i>
                        </button>
                        <button title="Редактировать" type="button" @click="editClient" class="px-2 max-sm:py-1">
                            <i class="fa fa-pencil text-xl text-blue-600" aria-hidden="true"></i>
                        </button>
                        <button v-if="$page.props.auth.role !== 'manager'" title="Удалить" type="button" @click="deleteClient" class="px-2 max-sm:py-1">
                            <i class="fa fa-trash text-xl text-red-700" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex gap-2 justify-between" v-if="!isEditing">
                            <p class="text-sm text-gray-500">
                                <strong>ID:</strong> {{ client.id }}<br>
                                <strong>Фамилия:</strong> {{ client.surname }}<br>
                                <strong>Имя:</strong> {{ client.name }}<br>
                                <strong>Отчество:</strong> {{ client.patronymic }}<br>
                                <strong>Дата рождения:</strong>
                                {{ client.birthdate ? dayjs(client.birthdate).format('DD.MM.YYYY') : '' }}<br>
                                <strong>Место работы:</strong> {{ client.workplace }}<br>
                                <strong>Телефон:</strong> {{ client.phone }}<br>
                                <strong>Почта:</strong> {{ client.email }}<br>
                                <strong>Телеграм:</strong> {{ client.telegram }}<br>
                                <strong>Инстаграм:</strong> {{ client.instagram }}<br>
                                <strong>Адрес:</strong> {{ client.address }}<br>
                                <strong>Пол:</strong>
                                <span v-if="client.gender === 'male'"> M</span>
                                <span v-else-if="client.gender === 'female'"> Ж</span>
                                <br>
                                <strong>Источник:</strong> {{ client.ad_source }}
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Дата первого обращения в клуб:</strong>
                                {{ client.created_at ? dayjs(client.created_at).format('DD.MM.YYYY HH:mm') : '' }}<br>
                                <span v-if="client.is_lead === 0">
                                    <strong>Дата первого посещения:</strong> {{ firstSaleDate }}<br>
                                    <strong>Дата перехода из лида в клиента:</strong> {{
                                        client.purchase_created_at ? dayjs(client.purchase_created_at).format('DD.MM.YYYY') : '–'
                                    }}
                                </span>
                            </p>
                        </div>
                        <form v-else @submit.prevent="submitEdit">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-1 items-end">
                                <div class="flex flex-col">
                                    <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                                    <input type="text" v-model="formEdit.surname"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.surname" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="name" class="text-sm font-medium text-gray-700">Имя<span
                                        class="text-red-600">*</span></label>
                                    <input type="text" required v-model="formEdit.name"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.name" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество</label>
                                    <input type="text" v-model="formEdit.patronymic"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.patronymic"
                                                class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="birthdate" class="text-sm font-medium text-gray-700">Дата
                                        рождения</label>
                                    <input type="date" v-model="formEdit.birthdate"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.birthdate" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="workplace" class="text-sm font-medium text-gray-700">Место
                                        работы</label>
                                    <input type="text" v-model="formEdit.workplace"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.workplace" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                                    <InputMask id="phone" v-model="formEdit.phone" mask="+7 (999) 999-99-99" placeholder="+7" class="p-1 border border-gray-300 rounded-md" fluid />
                                    <InputError :message="formEdit.errors.phone" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="email" class="text-sm font-medium text-gray-700">Почта</label>
                                    <input type="text" v-model="formEdit.email"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.email" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="telegram" class="text-sm font-medium text-gray-700">Телеграм</label>
                                    <input type="text" v-model="formEdit.telegram"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.telegram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="instagram" class="text-sm font-medium text-gray-700">Инстаграм</label>
                                    <input type="text" v-model="formEdit.instagram"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.instagram" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="address" class="text-sm font-medium text-gray-700">Адрес</label>
                                    <input type="text" v-model="formEdit.address"
                                           class="p-1 border border-gray-300 rounded-md"/>
                                    <InputError :message="formEdit.errors.address" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="gender" class="text-sm font-medium text-gray-700">Пол</label>
                                    <select v-model="formEdit.gender" class="p-1 border border-gray-300 rounded-md">
                                        <option value="male">М</option>
                                        <option value="female">Ж</option>
                                    </select>
                                    <InputError :message="formEdit.errors.gender" class="mt-2 text-sm text-red-600"/>
                                </div>
                                <div class="flex flex-col">
                                    <label for="ad_source" class="text-sm font-medium text-gray-700">Источник</label>
                                    <select id="ad_source" v-model="formEdit.ad_source"
                                            class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                                        <option v-for="source in sourceOptions"
                                                :value="source.name" :key="source.id">{{ source.name }}
                                        </option>
                                    </select>
                                    <InputError :message="formEdit.errors.ad_source" class="mt-2 text-sm text-red-600"/>
                                </div>
                            </div>
                            <div class="mt-2 mb-4 flex gap-2">
                                <PrimaryButton size="small" type="submit">Сохранить</PrimaryButton>
                                <SecondaryButton size="small" type="button" @click="isEditing = false">Отмена
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>
                    <PrimaryButton v-if="wazzupUser && client.phone" size="small" class="mt-2" @click="fetchIframeUrl">
                        Написать в WhatsApp
                    </PrimaryButton>
                    <form @submit.prevent="submit">
                        <div class="mt-2 flex">
                            <h3 class="text-md font-medium mr-2">Задача на дату</h3>
                            <div class="flex w-32">
                                <input type="date"
                                       v-model="formTask.task_date"
                                       class="p-0 pl-1 border border-gray-300 rounded-md" required
                                />
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea rows="3"
                                      v-model="formTask.task_description"
                                      class="w-full p-1 border border-gray-300 rounded-md text-sm"
                                      placeholder="Введите задачу по лиду/клиенту" required>
                            </textarea>
                        </div>
                        <secondary-button size="small" type="submit">Отправить задачу</secondary-button>
                    </form>
                    <div v-if="isTasksLoading">
                        <Spinner size="w-10 h-10" class="m-2"/>
                    </div>
                    <div v-else>
                        <div v-if="tasks.length > 0" class="mt-2">
                            <h4 class="text-md font-medium text-gray-700 mt-2">Все задачи на текущего клиента</h4>
                            <div class="overflow-x-auto mt-1">
                                <table class="w-full text-xs border-collapse border border-slate-600">
                                    <thead>
                                    <tr>
                                        <th class="p-1 border border-slate-600 text-left w-16">Дата</th>
                                        <th class="p-1 border border-slate-600 text-left w-16">Отправитель</th>
                                        <th class="p-1 border border-slate-600 text-left">Описание</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="task in tasks" :key="task.id" class="border-b">
                                        <td class="p-1 border border-slate-600 w-16">
                                            {{ task.task_date ? dayjs(task.task_date).format('DD.MM.YY') : '' }}
                                        </td>
                                        <td class="p-1 border border-slate-600">
                                            {{ task.user_sender.name }}
                                        </td>
                                        <td class="p-1 border border-slate-600">
                                            {{ task.task_description }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div v-if="isClientSalesLoading">
                        <Spinner size="w-10 h-10" class="m-2"/>
                    </div>
                    <div v-else>
                        <div v-if="clientSales.length > 0" class="mt-2">
                            <h4 class="text-md font-medium text-gray-700 mt-2">Все продажи на сумму:
                                {{ Number(totalSalesByClient) }} &#8381;</h4>
                            <div class="overflow-x-auto mt-1">
                                <table class="w-full text-xs border-collapse border border-slate-600">
                                    <thead>
                                    <tr>
                                        <th class="p-1 border border-slate-600 text-left">Дата</th>
                                        <th class="p-1 border border-slate-600 text-left">Вид продажи</th>
                                        <th class="p-1 border border-slate-600 text-left">Вид спорта</th>
                                        <th class="p-1 border border-slate-600 text-left">Тип услуги</th>
                                        <th class="p-1 border border-slate-600 text-left">Тип продукта</th>
                                        <th class="p-1 border border-slate-600 text-left">Длит. абонем.</th>
                                        <th class="p-1 border border-slate-600 text-left">Посещ. в нед.</th>
                                        <th class="p-1 border border-slate-600 text-left">Кол-во тренировок</th>
                                        <th class="p-1 border border-slate-600 text-left">Тренер</th>
                                        <th class="p-1 border border-slate-600 text-left">Категория тренера</th>
                                        <th class="p-1 border border-slate-600 text-left">Начало абонем.</th>
                                        <th class="p-1 border border-slate-600 text-left">Конец абонем.</th>
                                        <th class="p-1 border border-slate-600 text-left">Цена</th>
                                        <th class="p-1 border border-slate-600 text-left">Оплачено</th>
                                        <th class="p-1 border border-slate-600 text-left">Метод оплаты</th>
                                        <th class="p-1 border border-slate-600 text-left">Комментарий</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="sale in clientSales" :key="sale.id" class="border-b">
                                        <td class="p-1 border border-slate-600">
                                            {{ sale.sale_date ? dayjs(sale.sale_date).format('DD.MM.YY') : '' }}
                                        </td>
                                        <td class="p-1 border border-slate-600">
                                            <span v-if="sale.service_or_product === 'product'">Товар</span>
                                            <span v-else-if="sale.service_or_product === 'service'">Услуга</span>
                                        </td>
                                        <td class="p-1 border border-slate-600">{{ sale.sport_type }}</td>
                                        <td class="p-1 border border-slate-600">
                                            <span v-if="sale.service_type === 'trial'">Пробная</span>
                                            <span v-else-if="sale.service_type === 'group'">Групповая</span>
                                            <span v-else-if="sale.service_type === 'minigroup'">Минигруппа</span>
                                            <span v-else-if="sale.service_type === 'individual'">Индивидуальная</span>
                                            <span v-else-if="sale.service_type === 'split'">Сплит</span>
                                        </td>
                                        <td class="p-1 border border-slate-600">{{ sale.product_type }}</td>
                                        <td class="p-1 border border-slate-600">
                                            {{
                                                sale.subscription_duration === '0.03' ?
                                                    'Разовая' :
                                                    (sale.subscription_duration ? Number(sale.subscription_duration).toFixed(0) : '')
                                            }}
                                        </td>
                                        <td class="p-1 border border-slate-600">{{ sale.visits_per_week }}</td>
                                        <td class="p-1 border border-slate-600">{{ sale.training_count }}</td>
                                        <td class="p-1 border border-slate-600">{{ sale.trainer }}</td>
                                        <td class="p-1 border border-slate-600">{{ sale.trainer_category }}</td>
                                        <td class="p-1 border border-slate-600">
                                            {{
                                                sale.subscription_start_date ? dayjs(sale.subscription_start_date).format('DD.MM.YY') : ''
                                            }}
                                        </td>
                                        <td class="p-1 border border-slate-600">
                                            {{
                                                sale.subscription_end_date ? dayjs(sale.subscription_end_date).format('DD.MM.YY') : ''
                                            }}
                                        </td>
                                        <td class="p-1 border border-slate-600">{{ sale.cost }}</td>
                                        <td class="p-1 border border-slate-600">{{ sale.paid_amount }}</td>
                                        <td class="p-1 border border-slate-600">{{ sale.pay_method }}</td>
                                        <td class="p-1 border border-slate-600 max-w-64 break-words">{{ sale.comment }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
    <!-- Модальное окно для iframe -->
    <Modal :show="showIframe" :max-width="'7xl'" @close="showIframe = false">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <iframe v-if="iframeUrl" :src="iframeUrl" allow="microphone *; clipboard-read *; clipboard-write *" class="w-full h-[80vh]"></iframe>
        </div>
    </Modal>
</template>
