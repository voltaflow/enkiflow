import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig(({ command, mode }) => ({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx', 'resources/js/landing/enhanced-timer.js'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            // Copy landing scripts to public directory
            copy: [
                { 
                    from: 'resources/js/landing/*.js',
                    to: 'js/landing/[name][extname]'
                }
            ]
        }),
        react({
            jsxRuntime: 'automatic',
            // Fast Refresh solo en desarrollo
            fastRefresh: command === 'serve' && mode !== 'production',
            // Por si hay algún problema específico de React 
            include: '**/*.{jsx,tsx}',
        }),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
        jsxImportSource: 'react',
    },
    optimizeDeps: {
        include: ['react/jsx-runtime'],
    },
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
}));