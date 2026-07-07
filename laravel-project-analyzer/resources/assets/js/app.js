import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { InertiaProgress } from '@inertiajs/progress';
import Layout from './Layouts/DashboardLayout.vue';

InertiaProgress.init({ color: '#2563eb' });

createInertiaApp({
    title: (title) => (title ? `${title} - Project Analyzer` : 'Project Analyzer'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];

        if (! page) {
            throw new Error(`Project Analyzer dashboard page not found: ${name}`);
        }

        page.default.layout = page.default.layout || Layout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
