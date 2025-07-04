import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Get CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Set base URL to current origin to ensure proper routing
axios.defaults.baseURL = window.location.origin;

// Add interceptor to ensure tenant routes work correctly
axios.interceptors.request.use((config) => {
    // If the URL starts with http or https, it's already absolute
    if (config.url && (config.url.startsWith('http://') || config.url.startsWith('https://'))) {
        // Replace the domain with the current domain if it's localhost or different
        const url = new URL(config.url);
        if (url.hostname === 'localhost' || url.hostname !== window.location.hostname) {
            url.hostname = window.location.hostname;
            url.port = window.location.port;
            url.protocol = window.location.protocol;
            config.url = url.toString();
        }
    }
    return config;
});

export default axios;
