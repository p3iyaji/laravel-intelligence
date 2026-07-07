<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Component Explorer</h1>
            <input
                v-model="searchQuery"
                type="search"
                placeholder="Search components..."
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white w-64"
            />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <!-- Categories -->
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Categories</h3>
                    <button
                        v-for="(items, type) in components"
                        :key="type"
                        @click="selectCategory(type)"
                        :class="[
                            selectedType === type ? 'bg-blue-50 dark:bg-blue-900 text-blue-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                            'w-full text-left px-3 py-2 rounded-lg text-sm flex justify-between'
                        ]"
                    >
                        <span class="capitalize">{{ type }}</span>
                        <span class="text-gray-400">{{ Array.isArray(items) ? items.length : 0 }}</span>
                    </button>
                </div>
            </div>

            <!-- Component list -->
            <div :class="selectedItem ? 'xl:col-span-3' : 'xl:col-span-10'">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[calc(100vh-12rem)] overflow-y-auto">
                        <div
                            v-for="item in filteredItems"
                            :key="item.fqn"
                            :class="[
                                selectedItem?.fqn === item.fqn ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-750',
                                'p-4 cursor-pointer'
                            ]"
                            @click="selectItem(item)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-mono text-sm text-blue-600 dark:text-blue-400 truncate">{{ item.fqn }}</div>
                                    <div class="text-xs text-gray-500 mt-1 truncate">{{ item.file }}</div>
                                </div>
                                <span
                                    v-if="issueCountForItem(item) > 0"
                                    class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="worstSeverityClass(item)"
                                >
                                    {{ issueCountForItem(item) }}
                                </span>
                            </div>
                        </div>
                        <div v-if="filteredItems.length === 0" class="p-8 text-center text-gray-500">No components found</div>
                    </div>
                </div>
            </div>

            <!-- Detail panel -->
            <div v-if="selectedItem" class="xl:col-span-7 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-lg">{{ selectedItem.fqn }}</h3>
                            <p class="text-sm text-gray-500 mt-1 font-mono">{{ selectedItem.file }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-if="selectedItem.type" class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs">{{ selectedItem.type }}</span>
                            <span v-if="selectedItem.method_count" class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs">{{ selectedItem.method_count }} methods</span>
                            <span v-if="complexityInfo" class="rounded-full bg-purple-100 dark:bg-purple-900 px-2 py-1 text-xs text-purple-800 dark:text-purple-200">
                                complexity: {{ complexityInfo.complexity ?? complexityInfo.method_count }}
                            </span>
                        </div>
                    </div>

                    <div v-if="hasStructure" class="mb-4 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="selectedItem.extends">extends <span class="font-mono text-blue-600 dark:text-blue-400">{{ selectedItem.extends }}</span></span>
                        <span v-if="selectedItem.implements?.length">implements <span class="font-mono">{{ selectedItem.implements.join(', ') }}</span></span>
                        <span v-if="selectedItem.traits?.length">uses <span class="font-mono">{{ selectedItem.traits.join(', ') }}</span></span>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                        <nav class="flex gap-4">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                                    'pb-2 text-sm font-medium border-b-2'
                                ]"
                            >
                                {{ tab.label }}
                                <span v-if="tab.badge" class="ml-1 rounded-full bg-red-100 dark:bg-red-900 px-1.5 text-xs text-red-700 dark:text-red-200">{{ tab.badge }}</span>
                            </button>
                        </nav>
                    </div>

                    <!-- Methods tab -->
                    <div v-show="activeTab === 'methods'">
                        <div v-if="selectedItem.methods?.length" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                        <th class="pb-2 pr-4">Method</th>
                                        <th class="pb-2 pr-4">Visibility</th>
                                        <th class="pb-2 pr-4">Params</th>
                                        <th class="pb-2">Return</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="m in selectedItem.methods" :key="m.name" class="font-mono text-xs">
                                        <td class="py-2 pr-4 text-blue-600 dark:text-blue-400">
                                            <span v-if="m.is_static" class="text-gray-400">static </span>{{ m.name }}()
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="rounded px-1.5 py-0.5" :class="visibilityClass(m.visibility)">{{ m.visibility }}</span>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ m.parameters }}</td>
                                        <td class="py-2 text-gray-600 dark:text-gray-400">{{ m.return_type || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-gray-500">No methods found.</p>
                    </div>

                    <!-- Code tab -->
                    <div v-show="activeTab === 'code'">
                        <div v-if="highlightedLineCount > 0" class="mb-3 flex flex-wrap gap-3 text-xs">
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-500"></span> High severity</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-500"></span> Medium severity</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-400"></span> Low severity</span>
                        </div>
                        <SourceCodeViewer
                            :lines="sourceLines"
                            :highlighted-lines="highlightedLines"
                            :line-suggestions="lineSuggestions"
                            :loading="sourceLoading"
                            :error="sourceError"
                        />
                    </div>

                    <!-- Diagram tab -->
                    <div v-show="activeTab === 'diagram'" class="space-y-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Class Structure</h4>
                            <MermaidDiagram :id="`class-${diagramKey}`" :diagram="classDiagram" />
                        </div>
                        <div v-if="dependencyDiagram">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dependencies</h4>
                            <MermaidDiagram :id="`deps-${diagramKey}`" :diagram="dependencyDiagram" />
                        </div>
                    </div>

                    <!-- Issues tab -->
                    <div v-show="activeTab === 'issues'">
                        <div v-if="selectedIssues.length" class="space-y-3">
                            <div
                                v-for="(issue, index) in selectedIssues"
                                :key="`${issue.source}-${index}`"
                                class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium uppercase" :class="priorityClass(issue.severity ?? issue.priority)">
                                        {{ issue.severity ?? issue.priority }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs">{{ issue.source }}</span>
                                    <button
                                        v-if="issue.line"
                                        @click="goToLine(issue.line)"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        Line {{ issue.line }}
                                    </button>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ issue.message ?? issue.description ?? issue.title }}</p>
                                <p v-if="issue.impact" class="mt-1 text-xs text-gray-500">Impact: {{ issue.impact }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">No issues detected for this component.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import MermaidDiagram from '../../Components/MermaidDiagram.vue';
import SourceCodeViewer from '../../Components/SourceCodeViewer.vue';

const props = defineProps({
    components: Object,
    search: String,
    securityFindings: { type: Array, default: () => [] },
    costHotspots: { type: Array, default: () => [] },
    recommendations: { type: Array, default: () => [] },
    graphData: { type: Object, default: () => ({}) },
    complexity: { type: Object, default: () => ({}) },
    sourceUrl: { type: String, required: true },
});

const searchQuery = ref(props.search || '');
const selectedType = ref('models');
const selectedItem = ref(null);
const activeTab = ref('methods');
const sourceLines = ref([]);
const sourceLoading = ref(false);
const sourceError = ref('');

const severityRank = { high: 3, medium: 2, low: 1 };

const filteredItems = computed(() => {
    const items = props.components?.[selectedType.value] ?? [];
    if (! searchQuery.value) return items;
    const q = searchQuery.value.toLowerCase();
    return items.filter(i =>
        (i.fqn ?? '').toLowerCase().includes(q) ||
        (i.file ?? '').toLowerCase().includes(q)
    );
});

const issuesByFile = computed(() => {
    const map = {};

    const addIssue = (file, issue) => {
        if (! file) return;
        if (! map[file]) map[file] = [];
        map[file].push(issue);
    };

    for (const finding of props.securityFindings) {
        addIssue(finding.file, { ...finding, source: 'security' });
    }

    for (const hotspot of props.costHotspots) {
        addIssue(hotspot.file, { ...hotspot, source: 'cost' });
    }

    for (const rec of props.recommendations) {
        if (rec.file) {
            addIssue(rec.file, {
                ...rec,
                source: rec.category ?? 'recommendation',
                severity: rec.priority,
                message: rec.description ?? rec.title,
            });
        }
        if (rec.class && ! rec.file) {
            for (const items of Object.values(props.components ?? {})) {
                const match = (items ?? []).find(i => i.fqn === rec.class);
                if (match?.file) {
                    addIssue(match.file, {
                        ...rec,
                        source: rec.category ?? 'recommendation',
                        severity: rec.priority,
                        message: rec.description ?? rec.title,
                    });
                }
            }
        }
    }

    return map;
});

const selectedIssues = computed(() => {
    if (! selectedItem.value?.file) return [];
    return issuesByFile.value[selectedItem.value.file] ?? [];
});

const highlightedLines = computed(() => {
    const map = {};

    for (const issue of selectedIssues.value) {
        const severity = issue.severity ?? issue.priority ?? 'medium';
        const lines = issue.lines ?? (issue.line ? [issue.line] : []);

        for (const line of lines) {
            const lineNum = Number(line);
            if (! map[lineNum] || (severityRank[severity] ?? 0) > (severityRank[map[lineNum]] ?? 0)) {
                map[lineNum] = severity;
            }
        }
    }

    return map;
});

const lineSuggestions = computed(() => {
    const map = {};

    for (const issue of selectedIssues.value) {
        const suggestionText = resolveSuggestion(issue);
        if (! suggestionText) continue;

        const severity = issue.severity ?? issue.priority ?? 'medium';
        const lines = issue.lines ?? (issue.line ? [issue.line] : []);

        for (const line of lines) {
            const lineNum = Number(line);
            if (! map[lineNum]) map[lineNum] = [];

            const duplicate = map[lineNum].some(entry => entry.text === suggestionText);
            if (! duplicate) {
                map[lineNum].push({ text: suggestionText, severity });
            }
        }
    }

    return map;
});

function resolveSuggestion(issue) {
    if (issue.suggestion) {
        return issue.suggestion;
    }

    if (issue.impact) {
        return `${issue.message ?? issue.description ?? issue.title ?? 'Review this code'}. Impact: ${issue.impact}`;
    }

    return issue.message ?? issue.description ?? issue.title ?? null;
}

const highlightedLineCount = computed(() => Object.keys(highlightedLines.value).length);

const complexityInfo = computed(() => {
    if (! selectedItem.value?.fqn) return null;
    const largest = props.complexity?.largest_classes ?? [];
    const high = props.complexity?.high_complexity_classes ?? [];
    return [...largest, ...high].find(c => c.class === selectedItem.value.fqn) ?? null;
});

const hasStructure = computed(() =>
    selectedItem.value?.extends ||
    selectedItem.value?.implements?.length ||
    selectedItem.value?.traits?.length
);

const diagramKey = computed(() => (selectedItem.value?.fqn ?? 'none').replace(/[^a-zA-Z0-9]/g, '_'));

const classDiagram = computed(() => {
    if (! selectedItem.value) return '';
    return buildClassDiagram(selectedItem.value);
});

const dependencyDiagram = computed(() => {
    if (! selectedItem.value?.fqn) return '';
    return buildSubgraph(props.graphData, selectedItem.value.fqn);
});

const tabs = computed(() => [
    { id: 'methods', label: 'Methods' },
    { id: 'code', label: 'Code', badge: highlightedLineCount.value || null },
    { id: 'diagram', label: 'Diagram' },
    { id: 'issues', label: 'Issues', badge: selectedIssues.value.length || null },
]);

function selectCategory(type) {
    selectedType.value = type;
    selectedItem.value = null;
}

function selectItem(item) {
    selectedItem.value = item;
    activeTab.value = 'methods';
    loadSource(item.file);
}

function issueCountForItem(item) {
    return (issuesByFile.value[item.file] ?? []).length;
}

function worstSeverity(item) {
    const issues = issuesByFile.value[item.file] ?? [];
    let worst = null;

    for (const issue of issues) {
        const sev = issue.severity ?? issue.priority;
        if (! worst || (severityRank[sev] ?? 0) > (severityRank[worst] ?? 0)) {
            worst = sev;
        }
    }

    return worst;
}

function worstSeverityClass(item) {
    return {
        high: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    }[worstSeverity(item)] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
}

function visibilityClass(visibility) {
    return {
        public: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        protected: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        private: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    }[visibility] ?? 'bg-gray-100 text-gray-800';
}

function priorityClass(priority) {
    return {
        high: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    }[priority] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
}

async function loadSource(file) {
    sourceLines.value = [];
    sourceError.value = '';

    if (! file) return;

    sourceLoading.value = true;

    try {
        const response = await fetch(`${props.sourceUrl}?file=${encodeURIComponent(file)}`);
        const data = await response.json();

        if (! response.ok) {
            sourceError.value = data.error ?? 'Failed to load source';
            return;
        }

        sourceLines.value = data.lines ?? [];
    } catch {
        sourceError.value = 'Failed to load source code';
    } finally {
        sourceLoading.value = false;
    }
}

function goToLine(line) {
    activeTab.value = 'code';

    setTimeout(() => {
        const el = document.getElementById(`line-${line}`);
        el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

function sanitizeLabel(label) {
    return String(label).replace(/[^a-zA-Z0-9_]/g, '_') || 'Unknown';
}

function buildClassDiagram(classData) {
    const className = sanitizeLabel(classData.fqn?.split('\\').pop() ?? 'Unknown');
    const lines = ['classDiagram'];

    lines.push(`    class ${className} {`);
    for (const method of classData.methods ?? []) {
        const vis = { private: '-', protected: '#', public: '+' }[method.visibility] ?? '+';
        const ret = method.return_type && method.return_type !== 'mixed' ? ` ${method.return_type}` : '';
        const staticPrefix = method.is_static ? '{static} ' : '';
        lines.push(`        ${vis}${staticPrefix}${method.name}(${method.parameters ?? 0})${ret}`);
    }
    lines.push('    }');

    if (classData.extends) {
        lines.push(`    ${sanitizeLabel(classData.extends.split('\\').pop())} <|-- ${className}`);
    }

    for (const iface of classData.implements ?? []) {
        lines.push(`    ${sanitizeLabel(iface.split('\\').pop())} <|.. ${className}`);
    }

    for (const trait of classData.traits ?? []) {
        lines.push(`    ${sanitizeLabel(trait.split('\\').pop())} <|.. ${className} : uses`);
    }

    return lines.join('\n');
}

function buildSubgraph(graph, fqn) {
    const related = new Set([fqn]);

    for (const edge of graph.edges ?? []) {
        if (edge.from === fqn) related.add(edge.to);
        if (edge.to === fqn) related.add(edge.from);
    }

    const nodes = (graph.nodes ?? []).filter(n => related.has(n.id));
    const edges = (graph.edges ?? []).filter(e => related.has(e.from) && related.has(e.to));

    if (edges.length === 0) return '';

    const lines = ['graph TD'];

    for (const node of nodes) {
        const id = sanitizeLabel(node.id);
        const label = (node.id ?? '').replace(/"/g, '');
        const type = node.type ?? 'class';
        lines.push(`    ${id}["${label}<br/><small>${type}</small>"]`);
    }

    for (const edge of edges) {
        lines.push(`    ${sanitizeLabel(edge.from)} -->|${edge.type ?? ''}| ${sanitizeLabel(edge.to)}`);
    }

    return lines.join('\n');
}

watch(() => props.search, (value) => {
    searchQuery.value = value || '';
});
</script>
