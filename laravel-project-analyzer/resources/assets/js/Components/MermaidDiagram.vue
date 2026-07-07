<template>
    <div ref="container" class="overflow-auto min-h-[200px]"></div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import mermaid from 'mermaid';

const props = defineProps({
    diagram: { type: String, default: '' },
    id: { type: String, required: true },
});

const container = ref(null);
let renderCounter = 0;

async function render() {
    if (!props.diagram || !container.value) {
        if (container.value) {
            container.value.innerHTML = '';
        }
        return;
    }

    mermaid.initialize({
        startOnLoad: false,
        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
    });

    renderCounter += 1;
    const renderId = `${props.id}-${renderCounter}`;

    try {
        const { svg } = await mermaid.render(renderId, props.diagram);
        container.value.innerHTML = svg;
    } catch {
        container.value.innerHTML = '<p class="text-sm text-gray-500 p-4">Unable to render diagram.</p>';
    }
}

onMounted(render);
watch(() => props.diagram, render);
</script>
