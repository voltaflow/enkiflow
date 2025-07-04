import '../css/app.css';

// Importar el polyfill de React Refresh
import './react-refresh.js';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { GlobalProviders } from './components/GlobalProviders';
import { initializeTheme } from './hooks/use-appearance';
import './lib/route-helper'; // Import our custom route helper

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Asegurar que el DOM esté completamente cargado antes de inicializar Inertia
document.addEventListener('DOMContentLoaded', () => {
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
        setup({ el, App, props }) {
            // Verificar que el elemento exista antes de crear el root
            if (el) {
                const root = createRoot(el);
                root.render(
                    <GlobalProviders>
                        <App {...props} />
                    </GlobalProviders>,
                );
            } else {
                console.error('El elemento para montar la aplicación no existe');
            }
        },
        progress: {
            color: '#4B5563',
        },
    });

    // This will set light / dark mode on load...
    initializeTheme();
});
