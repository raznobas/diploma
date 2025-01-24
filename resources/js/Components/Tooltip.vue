<template>
    <div>
        <!-- Триггер для тултипа -->
        <button
            type="button"
            :data-tooltip-target="tooltipId"
            :data-tooltip-placement="position"
            class="focus:outline-none cursor-auto"
        >
            <slot name="trigger">
                <!-- Дефолтная иконка, если слот не передан -->
                <i class="fa fa-3 fa-question-circle-o cursor-help" aria-hidden="true"></i>
            </slot>
        </button>

        <!-- Сам тултип -->
        <div
            :id="tooltipId"
            role="tooltip"
            class="tooltip-custom absolute z-10 invisible inline-block px-2 py-2 text-white bg-gray-800 rounded-lg shadow-sm opacity-0 tooltip normal-case max-w-sm whitespace-normal break-words"
        >
            {{ content }}
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { initTooltips } from 'flowbite';

// Генерация уникального ID для тултипа
const tooltipId = ref(`tooltip-${Math.random().toString(36).substring(2, 9)}`);

// Пропсы
const props = defineProps({
    content: {
        type: String,
        required: true,
    },
    position: {
        type: String
    },
});

// Инициализация тултипов Flowbite
onMounted(() => {
    initTooltips();
});
</script>
<style scoped>
.tooltip-custom {
    display: none;
}

.tooltip-custom.visible {
    display: inline-block;
}
</style>
