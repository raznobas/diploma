<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Вход"/>

    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
        {{ status }}
    </div>
    <div class="body">
        <div class="page">
            <div class="container">
                <div class="left border-black border-t-2">
                    <div class="login">Вход</div>
                    <div class="eula">
                        Войдите в сервис, чтобы мы вас узнали
                    </div>
                </div>
                <div class="right">
                    <form class="form" @submit.prevent="submit">
                        <div>
                            <label class="text-white" for="email">
                                Почта
                            </label>

                            <input
                                id="email"
                                type="email"
                                class="text-black"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <InputError class="mt-2" :message="form.errors.email"/>
                        </div>

                        <div class="mt-4">
                            <label class="text-white" for="email">
                                Пароль
                            </label>

                            <input
                                id="password"
                                type="password"
                                class="mt-1 block w-full text-black"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password"/>
                        </div>

                        <div class="block mt-4">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember"/>
                                <span class="ms-2 text-sm">Запомнить меня</span>
                            </label>
                        </div>

                        <div class="flex items-end justify-between mt-4">
                            <Link
                                :href="route('register')"
                                class="underline text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Нет аккаунта?
                            </Link>

                            <SecondaryButton id="submit" type="submit" class="ms-4" :class="{ 'opacity-25': form.processing }"
                                             :disabled="form.processing">
                                Войти
                            </SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://rsms.me/inter/inter-ui.css');

.body {
    background: white;
    font-family: 'Inter UI', sans-serif;
    margin: 0;
}

.page {
    display: flex;
    flex-direction: column;
    height: calc(100% - 40px);
    position: absolute;
    place-content: center;
    width: 100%;
}

@media (max-width: 767px) {
    .page {
        height: auto;
        margin-bottom: 20px;
        padding-bottom: 20px;
    }
}

.container {
    display: flex;
    margin: 0 auto;
    width: 640px;
}

@media (max-width: 767px) {
    .container {
        flex-direction: column;
        height: 630px;
        width: 320px;
    }
}

.left {
    background: white;
    height: calc(100% - 40px);
    top: 20px;
    position: relative;
    width: 50%;
}

@media (max-width: 767px) {
    .left {
        height: 100%;
        left: 20px;
        width: calc(100% - 40px);
        max-height: 270px;
    }
}

.login {
    font-size: 50px;
    font-weight: 900;
    margin: 50px 40px 40px;
}

.eula {
    color: #999;
    font-size: 14px;
    line-height: 1.5;
    margin: 40px;
}

.right {
    background: #474A59;
    box-shadow: 0px 0px 40px 16px rgba(0, 0, 0, 0.22);
    color: #F1F1F2;
    position: relative;
    width: 50%;
}

@media (max-width: 767px) {
    .right {
        flex-shrink: 0;
        height: 100%;
        width: 100%;
        max-height: 350px;
    }
}

.form {
    margin: 30px;
}

label {
    color: #c2c2c5;
    display: block;
    font-size: 14px;
    height: 16px;
    margin-top: 0;
    margin-bottom: 5px;
}

input:not([type="checkbox"]) {
    border: 0;
    font-size: 16px;
    line-height: 30px;
    outline: none !important;
    width: 100%;
}

</style>
