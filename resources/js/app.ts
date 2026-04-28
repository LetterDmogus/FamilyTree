import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const page = resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue'));

        page.then((module) => {
            if (name === 'Welcome') {
                module.default.layout = null;
            } else if (name.startsWith('auth/')) {
                module.default.layout = AuthLayout;
            } else if (name.startsWith('settings/')) {
                module.default.layout = (h, page) => h(AppLayout, () => h(SettingsLayout, () => page));
            } else {
                module.default.layout = AppLayout;
            }
        });

        return page;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// initializeFlashToast is now handled by FlashNotification component
// initializeFlashToast();
