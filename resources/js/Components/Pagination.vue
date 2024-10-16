<template>
    <nav class="mt-4">
        <ul class="flex justify-center">
            <li v-for="link in getVisibleLinks()" :key="link.label" class="mx-1">
                <Link :href="link.url || ''" v-html="getLocalizedLabel(link.label)" class="px-2 py-1 rounded-md"
                      :class="{ 'bg-gray-800 text-white': link.active }"/>
            </li>
        </ul>
        <div v-if="items && items.total" class="mt-3 text-center">
            Всего записей: {{ items.total }}
        </div>
    </nav>
</template>

<script setup>
import {Link} from '@inertiajs/vue3';

const props = defineProps({
    items: Object,
    pageParam: String, // параметр для имени параметра запроса (нужно для того, чтобы не было конфликтов, когда на странице две пагинации)
});

const getLocalizedLabel = (label) => {
    if (label === '&laquo; Previous') {
        return '&laquo; Предыдущая';
    } else if (label === 'Next &raquo;') {
        return 'Следующая &raquo;';
    }
    return label;
};

const visiblePages = 5; // Количество видимых страниц вокруг текущей

const getVisibleLinks = () => {
    const links = props.items.links;
    const currentPage = links.find(link => link.active)?.label || 1;
    const startPage = Math.max(1, currentPage - Math.floor(visiblePages / 2));
    const endPage = Math.min(links.length - 2, startPage + visiblePages - 1);

    const visibleLinks = [];

    if (startPage > 1) {
        visibleLinks.push(links[0]); // Первая страница
        if (startPage > 2) {
            visibleLinks.push({label: '...', url: null});
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        visibleLinks.push(links[i]);
    }

    if (endPage < links.length - 2) {
        if (endPage < links.length - 3) {
            visibleLinks.push({label: '...', url: null});
        }
        visibleLinks.push(links[links.length - 1]); // Последняя страница
    }

    return visibleLinks;
};
</script>

<style scoped>
.pagination li {
    display: inline-block;
}

.pagination .disabled {
    opacity: 0.5;
    pointer-events: none;
}

.pagination .ellipsis {
    pointer-events: none;
}
</style>
