<template>
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-gray-500">Loading source code...</div>
        <div v-else-if="error" class="p-8 text-center text-sm text-red-500">{{ error }}</div>
        <div v-else-if="!lines.length" class="p-8 text-center text-sm text-gray-500">No source available.</div>
        <div v-else class="overflow-auto max-h-[480px]">
            <table class="w-full text-xs font-mono border-collapse">
                <tbody>
                    <template v-for="(line, index) in lines" :key="index">
                        <tr
                            :id="`line-${index + 1}`"
                            :class="lineClass(index + 1)"
                        >
                            <td class="select-none text-right pr-3 pl-3 py-0.5 text-gray-400 dark:text-gray-500 border-r border-gray-200 dark:border-gray-700 w-12 align-top">
                                {{ index + 1 }}
                            </td>
                            <td class="px-3 py-0.5 whitespace-pre text-gray-800 dark:text-gray-200 align-top">{{ line }}</td>
                        </tr>
                        <tr
                            v-for="(suggestion, suggestionIndex) in suggestionsForLine(index + 1)"
                            :key="`suggestion-${index + 1}-${suggestionIndex}`"
                            :class="suggestionRowClass(suggestion.severity)"
                        >
                            <td class="border-r border-gray-200 dark:border-gray-700"></td>
                            <td class="px-3 py-1 whitespace-pre-wrap align-top italic" :class="suggestionTextClass(suggestion.severity)">
                                {{ formatSuggestionComment(suggestion) }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    lines: { type: Array, default: () => [] },
    highlightedLines: { type: Object, default: () => ({}) },
    lineSuggestions: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const severityRank = { high: 3, medium: 2, low: 1 };

const lineSeverity = computed(() => {
    const map = {};

    for (const [line, severity] of Object.entries(props.highlightedLines)) {
        const lineNum = Number(line);
        if (! map[lineNum] || (severityRank[severity] ?? 0) > (severityRank[map[lineNum]] ?? 0)) {
            map[lineNum] = severity;
        }
    }

    return map;
});

function suggestionsForLine(lineNumber) {
    return props.lineSuggestions[lineNumber] ?? [];
}

function formatSuggestionComment(suggestion) {
    const label = (suggestion.severity ?? 'medium').toUpperCase();
    return `// [${label}] Recommendation: ${suggestion.text}`;
}

function lineClass(lineNumber) {
    const severity = lineSeverity.value[lineNumber];

    if (! severity) {
        return 'hover:bg-gray-50 dark:hover:bg-gray-750';
    }

    return {
        high: 'bg-red-100 dark:bg-red-950/60 border-l-4 border-l-red-500',
        medium: 'bg-yellow-100 dark:bg-yellow-950/60 border-l-4 border-l-yellow-500',
        low: 'bg-blue-100 dark:bg-blue-950/60 border-l-4 border-l-blue-400',
    }[severity] ?? 'bg-orange-100 dark:bg-orange-950/60 border-l-4 border-l-orange-400';
}

function suggestionRowClass(severity) {
    return {
        high: 'bg-red-50/80 dark:bg-red-950/30',
        medium: 'bg-yellow-50/80 dark:bg-yellow-950/30',
        low: 'bg-blue-50/80 dark:bg-blue-950/30',
    }[severity] ?? 'bg-gray-50 dark:bg-gray-800/50';
}

function suggestionTextClass(severity) {
    return {
        high: 'text-red-700 dark:text-red-300',
        medium: 'text-yellow-800 dark:text-yellow-300',
        low: 'text-blue-700 dark:text-blue-300',
    }[severity] ?? 'text-gray-600 dark:text-gray-400';
}
</script>
