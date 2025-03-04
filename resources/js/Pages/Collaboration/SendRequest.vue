<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, useForm, usePage} from '@inertiajs/vue3';
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "@/useToast";
import DangerButton from "@/Components/DangerButton.vue";
import {ref} from "vue";
import Spinner from "@/Components/Spinner.vue";

const {showToast} = useToast();

const form = useForm({
    director_email: '',
    manager_id: usePage().props.auth.user.id,
    manager_email: usePage().props.auth.user.email,
});
const isLoading = ref(false);

// Получаем текущую заявку менеджера
const currentRequest = ref(null);
const getCurrentRequest = async (managerId) => {
    isLoading.value = true;
    currentRequest.value = (await axios.get(route('collaboration.getCurrentRequest', managerId))).data;
    isLoading.value = false;
};
getCurrentRequest(form.manager_id);
// Функция для отмены заявки
const deleteRequest = (requestId) => {
    if (confirm("Вы уверены, что хотите отозвать заявку?")) {
        form.post(route('collaboration.delete-request', {id: requestId}), {
            onSuccess: () => {
                form.reset();
                getCurrentRequest(form.manager_id);
                showToast("Заявка успешно отозвана.", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};
const submit = () => {
    form.post(route('collaboration.send-request'), {
        onSuccess: () => {
            form.reset();
            getCurrentRequest(form.manager_id);
            showToast("Заявка успешно отправлена. Ожидайте решения директора", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
</script>

<template>
    <Head title="Подача заявки"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Подача заявки директору</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="isLoading">
                        <Spinner size="w-10 h-10" class="m-2"/>
                    </div>
                    <div v-else>
                        <form v-if="!currentRequest" @submit.prevent="submit" class="text-gray-900">
                            Введите Email адрес директора, в команду которому вы хотите попасть. Аккаунт директора должен
                            быть зарегистрирован в системе.

                            <div class="mt-4">
                                <InputLabel for="email" value="Почта"/>

                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 w-1/2"
                                    v-model="form.director_email"
                                    placeholder="example@mail.ru"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.director_email"/>
                            </div>
                            <PrimaryButton class="mt-3" :class="{ 'opacity-25': form.processing }"
                                           :disabled="form.processing">
                                Отправить заявку
                            </PrimaryButton>
                        </form>

                        <div v-if="currentRequest && currentRequest.status !== 'approved'" class="text-gray-900">
                        <span v-if="currentRequest.status === 'rejected'">
                            Ваша текущая заявка была отклонена.
                        </span>
                            <span v-else>
                            У вас уже есть активная заявка, чтобы подать другую, отмените текущую
                        </span>
                            <h3 class="text-xl mt-4 mb-1 font-bold text-gray-800 leading-tight">Текущая заявка: </h3>
                            <p><strong>Почта директора:</strong> {{ currentRequest.director_email }}</p>
                            <p><strong>Статус:</strong>
                                <span v-if="currentRequest.status === 'pending'">
                                    В ожидании
                            </span>
                                <span v-else-if="currentRequest.status === 'approved'">
                                    Одобрено
                            </span>
                                <span v-else-if="currentRequest.status === 'rejected'">
                                    Отклонено
                            </span>
                            </p>
                            <DangerButton class="mt-3" @click="deleteRequest(currentRequest.id)">
                            <span v-if="currentRequest.status === 'rejected'">
                                Подать другую заявку
                            </span>
                                <span v-else>
                                Отменить заявку
                            </span>
                            </DangerButton>
                        </div>
                        <div v-else-if="currentRequest && currentRequest.status === 'approved'">
                            <h3 class="font-semibold text-lg text-gray-800 leading-tight">Email вашего текущего директора:
                                {{ currentRequest.director_email }}</h3>
                            <p class="py-1">Чтобы отправить заявку другому директору, удалите прикрепление от текущего</p>
                            <DangerButton class="mt-4" @click="deleteRequest(currentRequest.id)">
                                Удалить прикрепление к директору
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
