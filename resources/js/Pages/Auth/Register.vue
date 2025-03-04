<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import SecondaryButton from "@/Components/SecondaryButton.vue";

const form = useForm({
    name: '',
    email: '',
    account_type: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Регистрация"/>
    <div class="body">
        <div class="page">
            <div class="container">
                <div class="left border-black border-t-2">
                    <div class="login">Регистрация</div>
                    <div class="eula">
                        Зарегистрируйтесь и управляйте своим клубом
                    </div>
                </div>
                <div class="right">
                    <form class="form" @submit.prevent="submit">
                        <div>
                            <label for="name">Имя</label>

                            <input
                                id="name"
                                type="text"
                                class="mt-1 block w-full text-black"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                            />

                            <InputError class="mt-2" :message="form.errors.name"/>
                        </div>

                        <div class="mt-4">
                            <label for="email">Email</label>

                            <input
                                id="email"
                                type="email"
                                class="mt-1 block w-full text-black"
                                v-model="form.email"
                                required
                                autocomplete="username"
                            />

                            <InputError class="mt-2" :message="form.errors.email"/>
                        </div>

                        <div class="mt-4">
                            <label for="account_type">Тип аккаунта</label>
                            <select id="account_type" required v-model="form.account_type"
                                    class="pe-8 block w-full border border-gray-300"
                            >
                                <option value="Director">Директор</option>
                                <option value="Manager">Менеджер</option>
                            </select>

                            <InputError class="mt-2" :message="form.errors.account_type"/>
                        </div>

                        <div class="mt-4">
                            <label for="password">Пароль</label>
                            <input
                                id="password"
                                type="password"
                                class="mt-1 block w-full text-black"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password"/>
                        </div>

                        <div class="mt-4">
                            <label for="password_confirmation">
                                Подтверждение пароля
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                class="mt-1 block w-full text-black"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password_confirmation"/>
                        </div>

                        <div class="flex items-end justify-between mt-4">
                            <Link
                                :href="route('login')"
                                class="underline text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Уже есть аккаунт?
                            </Link>

                            <SecondaryButton id="submit" type="submit" class="ms-4"
                                             :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Зарегистрироваться
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
    width: 890px;
}

@media (max-width: 767px) {
    .container {
        flex-direction: column;
        height: 630px;
        width: 100%;
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

@media (max-width: 1024px) {
    .login, .eula {
        margin: 30px 20px !important;
    }
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
        width: 100%;
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

input, select {
    border: 0;
    font-size: 16px;
    color: black;
    line-height: 30px;
    outline: none !important;
    width: 100%;
}

input::-moz-focus-inner {
    border: 0;
}

#submit {
    color: #707075;
    margin-top: 40px;
    transition: color 300ms;
}

#submit:focus {
    color: #f2f2f2;
}

#submit:active {
    color: #d0d0d2;
}

</style>
