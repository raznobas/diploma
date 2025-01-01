<script setup>
import { ref } from 'vue';
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    fields: {
        type: Array,
        required: true
    },
    filterForm: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:filterForm', 'resetFilters']);

const showFilters = ref(false);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const handleInput = (field, value) => {
    emit('update:filterForm', field, value);
};

const resetFilters = () => {
    emit('resetFilters');
};
</script>
<template>

    <div class="mb-5">
        <button class="text-md mb-2 font-medium text-gray-700 cursor-pointer flex items-center uppercase" @click="toggleFilters">
            Фильтры
            <span v-if="showFilters" class="ml-1">&#9650;</span>
            <span v-else class="ml-1">&#9660;</span>
        </button>
        <div v-if="showFilters">
            <SecondaryButton :size="'small'" class="mb-3" type="button" @click="resetFilters">Очистить все</SecondaryButton>
            <div class="grid md:grid-cols-8 max-md:grid-cols-3 gap-1 md:gap-2 text-sm">
                <div v-for="field in fields" :key="field.name">
                    <label :for="`search_${field.name}`" class="block text-xs font-medium text-gray-700">{{ field.label }}</label>
                    <input
                        v-if="field.type === 'text' || field.type === 'date' || field.type === 'email'"
                        :value="filterForm[field.name]"
                        :type="field.type"
                        :id="`search_${field.name}`"
                        class="mt-1 p-1 block border border-gray-300 w-full max-sm:text-sm"
                        @input="handleInput(field.name, $event.target.value)"
                    />
                    <select
                        v-else-if="field.type === 'select'"
                        :value="filterForm[field.name]"
                        :id="`search_${field.name}`"
                        class="mt-1 p-1 block border border-gray-300 w-full max-sm:text-sm"
                        @change="handleInput(field.name, $event.target.value)"
                    >
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
