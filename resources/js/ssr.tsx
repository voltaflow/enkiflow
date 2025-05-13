import React from 'react';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { type RouteName, route } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Agregar verificación para el entorno SSR
try {
    createServer((page) =>
        createInertiaApp({
            page,
            render: ReactDOMServer.renderToString,
            title: (title) => `${title} - ${appName}`,
            resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
            setup: ({ App, props }) => {
                try {
                    /* eslint-disable */
                    // @ts-expect-error
                    global.route<RouteName> = (name, params, absolute) =>
                        route(name, params as any, absolute, {
                            // @ts-expect-error
                            ...page.props.ziggy,
                            // @ts-expect-error
                            location: new URL(page.props.ziggy.location),
                        });
                    /* eslint-enable */

                    return <App {...props} />;
                } catch (error) {
                    console.error('Error en la configuración de SSR:', error);
                    return <div>Error al cargar la aplicación</div>;
                }
            },
        }),
    );
} catch (error) {
    console.error('Error al inicializar el servidor SSR:', error);
}
