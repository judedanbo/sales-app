<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BookOpen, Clock, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface TableOfContentsItem {
    level: number;
    title: string;
    anchor: string;
}

interface Props {
    markdownContent: string;
    tableOfContents: TableOfContentsItem[];
    lastModified: number;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const markdownHtml = ref('');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documentation',
        href: '/docs',
    },
];

// Convert markdown to HTML (basic conversion)
const convertMarkdownToHtml = (markdown: string): string => {
    let html = markdown;

    // Headers
    html = html.replace(/^# (.+)$/gm, '<h1 id="$1" class="text-4xl font-bold mb-6 text-gray-900 dark:text-gray-100">$1</h1>');
    html = html.replace(/^## (.+)$/gm, (match, title) => {
        const anchor = title
            .toLowerCase()
            .replace(/[^\w\s]/g, '')
            .replace(/\s+/g, '-');
        return `<h2 id="${anchor}" class="text-3xl font-semibold mt-8 mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">${title}</h2>`;
    });
    html = html.replace(/^### (.+)$/gm, (match, title) => {
        const anchor = title
            .toLowerCase()
            .replace(/[^\w\s]/g, '')
            .replace(/\s+/g, '-');
        return `<h3 id="${anchor}" class="text-2xl font-semibold mt-6 mb-3 text-gray-700 dark:text-gray-300">${title}</h3>`;
    });
    html = html.replace(/^#### (.+)$/gm, (match, title) => {
        const anchor = title
            .toLowerCase()
            .replace(/[^\w\s]/g, '')
            .replace(/\s+/g, '-');
        return `<h4 id="${anchor}" class="text-xl font-semibold mt-4 mb-2 text-gray-600 dark:text-gray-400">${title}</h4>`;
    });

    // Bold and italic
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em class="italic">$1</em>');

    // Code blocks
    html = html.replace(
        /```(\w+)?\n([\s\S]*?)```/g,
        '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto my-4"><code class="text-sm">$2</code></pre>',
    );
    html = html.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono">$1</code>');

    // Lists
    html = html.replace(/^- (.+)$/gm, '<li class="ml-4 mb-1">• $1</li>');
    html = html.replace(/^(\d+)\. (.+)$/gm, '<li class="ml-4 mb-1">$1. $2</li>');

    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-600 dark:text-blue-400 hover:underline">$1</a>');

    // Horizontal rules
    html = html.replace(/^---$/gm, '<hr class="my-8 border-gray-300 dark:border-gray-600">');

    // Paragraphs
    html = html.replace(/^(.+)$/gm, (match, line) => {
        if (
            line.trim() === '' ||
            line.startsWith('<h') ||
            line.startsWith('<li') ||
            line.startsWith('<pre') ||
            line.startsWith('<hr') ||
            line.startsWith('<code')
        ) {
            return line;
        }
        return `<p class="mb-4 text-gray-700 dark:text-gray-300 leading-relaxed">${line}</p>`;
    });

    return html;
};

const filteredToc = computed(() => {
    if (!searchQuery.value) return props.tableOfContents;

    return props.tableOfContents.filter((item) => item.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const formatLastModified = computed(() => {
    return new Date(props.lastModified * 1000).toLocaleDateString();
});

const scrollToSection = (anchor: string) => {
    const element = document.getElementById(anchor);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    markdownHtml.value = convertMarkdownToHtml(props.markdownContent);
});
</script>

<template>
    <Head title="Documentation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full">
            <!-- Sidebar - Table of Contents -->
            <div class="w-80 border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contents</h2>
                    </div>

                    <!-- Search -->
                    <div class="relative mb-4">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <Input v-model="searchQuery" placeholder="Search documentation..." class="pl-10" />
                    </div>

                    <!-- Table of Contents -->
                    <div class="h-[calc(100vh-280px)] overflow-y-auto">
                        <div class="space-y-1">
                            <button
                                v-for="item in filteredToc"
                                :key="item.anchor"
                                @click="scrollToSection(item.anchor)"
                                class="block w-full rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
                                :class="{
                                    'pl-3': item.level === 1,
                                    'pl-6': item.level === 2,
                                    'pl-9': item.level === 3,
                                    'font-medium text-gray-900 dark:text-gray-100': item.level === 1,
                                    'text-gray-700 dark:text-gray-300': item.level === 2,
                                    'text-gray-600 dark:text-gray-400': item.level === 3,
                                }"
                            >
                                {{ item.title }}
                            </button>
                        </div>
                    </div>

                    <!-- Last Modified -->
                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <Clock class="h-3 w-3" />
                            <span>Updated: {{ formatLastModified }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 bg-white dark:bg-gray-900">
                <div class="h-[calc(100vh-80px)] overflow-y-auto">
                    <div class="mx-auto max-w-4xl p-8">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <BookOpen class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    User Manual
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="prose prose-gray dark:prose-invert documentation-content max-w-none" v-html="markdownHtml" />
                            </CardContent>
                        </Card>

                        <!-- Footer -->
                        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <Separator class="mb-4" />
                            <p>For additional help, contact your system administrator</p>
                            <p class="mt-2">Last updated: {{ formatLastModified }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom styles for better markdown rendering */
.documentation-content :deep(h1),
.documentation-content :deep(h2),
.documentation-content :deep(h3),
.documentation-content :deep(h4) {
    scroll-margin-top: 2rem;
}

.documentation-content :deep(pre) {
    background-color: rgb(243 244 246);
    border-radius: 0.5rem;
    padding: 1rem;
    margin: 1rem 0;
    overflow-x: auto;
}

.dark .documentation-content :deep(pre) {
    background-color: rgb(31 41 55);
}

.documentation-content :deep(code) {
    background-color: rgb(243 244 246);
    border-radius: 0.375rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
}

.dark .documentation-content :deep(code) {
    background-color: rgb(31 41 55);
}

.documentation-content :deep(li) {
    margin: 0.25rem 0;
}

.documentation-content :deep(ul) {
    list-style: none;
    padding-left: 0;
}

.documentation-content :deep(ul li) {
    position: relative;
    padding-left: 1.5rem;
}

.documentation-content :deep(ul li::before) {
    content: '•';
    position: absolute;
    left: 0.5rem;
    color: rgb(37 99 235);
    font-weight: bold;
}
</style>
