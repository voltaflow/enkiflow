import { route as ziggyRoute } from 'ziggy-js';

/**
 * Custom route helper that ensures tenant routes use the correct domain
 */
export function route(name: string, params?: any, absolute?: boolean): string {
    // Get the URL from Ziggy
    let url = ziggyRoute(name, params, absolute);
    
    // If it's a tenant route and the URL has localhost or wrong domain, fix it
    if (name.startsWith('tenant.') && url.includes('://')) {
        const generatedUrl = new URL(url);
        const currentUrl = new URL(window.location.href);
        
        // If the generated URL has localhost or different domain, use current domain
        if (generatedUrl.hostname === 'localhost' || generatedUrl.hostname !== currentUrl.hostname) {
            generatedUrl.hostname = currentUrl.hostname;
            generatedUrl.port = currentUrl.port;
            generatedUrl.protocol = currentUrl.protocol;
            url = generatedUrl.toString();
        }
    }
    
    return url;
}

// Make it available globally to match Laravel's convention
if (typeof window !== 'undefined') {
    (window as any).route = route;
}

export default route;