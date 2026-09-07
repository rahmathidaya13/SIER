<script setup>
import { router } from "@inertiajs/vue3";
import { cleanFilters } from "@/Helpers/filters";
const props = defineProps({
    links: Array,
    keyword: {
        type: String,
        default: '',
    },
    routeName: {
        type: String,
        required: true,
    },
    additionalQuery: {
        type: Object,
        default: () => ({}),
    },
    size: {
        type: String,
        default: '',
    },
    only: {
        type: Array,
        default: () => [],
    }
})

const goToPage = (url) => {
    if (!url) return

    const queryString = new URL(url).search
    const params = new URLSearchParams(queryString)
    const page = params.get('page')

    const payload = {
        ...props.additionalQuery,
        page,
    }
    router.get(route(props.routeName), cleanFilters(payload), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: props.only
    })
}
</script>
<template>
    <nav aria-label="Page navigation">
        <ul :class="['pagination justify-content-end', props.size]">
            <!-- Prev -->
            <li :class="['page-item', { disabled: !links[0].url }]">
                <button class="page-link" @click.prevent="goToPage(links[0].url)" :disabled="!links[0].url">
                    &laquo;
                </button>
            </li>
            <!-- Number -->
            <li v-for="(link, index) in links.slice(1, links.length - 1)" :key="index"
                :class="['page-item', { active: link.active }]">
                <button v-if="link.url" class="page-link" @click.prevent="goToPage(link.url)" v-html="link.label" />
                <span v-else class="page-link" v-html="link.label" />
            </li>

            <!-- Next -->
            <li :class="['page-item', { disabled: !links[links.length - 1].url }]">
                <button class="page-link" @click.prevent="goToPage(links[links.length - 1].url)"
                    :disabled="!links[links.length - 1].url">
                    &raquo;
                </button>
            </li>
        </ul>
    </nav>
</template>
