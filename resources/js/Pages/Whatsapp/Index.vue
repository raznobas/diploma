<script setup>

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head, usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import {useToast} from "@/useToast.js";
const {showToast} = useToast();

const iframeUrl = ref(null);
const showIframe = ref(false);
const wazzupUser = usePage().props.auth.wazzup_user;

const fetchIframeUrl = async () => {
    try {
        // Данные, которые отправляются на бэкенд
        const data = {
            user: {
                id: wazzupUser.id,
                name: wazzupUser.name,
            },
            scope: 'global'
        };

        // Отправляем POST-запрос на бэкенд
        const response = await axios.post(route('whatsapp.getIframeUrl'), data);
        iframeUrl.value = response.data.url;
        showIframe.value = true;
    } catch (error) {
        showToast("Произошла ошибка при открытии чата", "error");
        console.error('Ошибка при получении iframe URL:', error);
    }
};

onMounted(() => {
    fetchIframeUrl();
});
</script>

<template>
    <Head title="WhatsApp"/>

    <AuthenticatedLayout>
        <iframe v-if="iframeUrl" :src="iframeUrl" allow="microphone *; clipboard-read *; clipboard-write *" class="w-full h-[90vh]"></iframe>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
