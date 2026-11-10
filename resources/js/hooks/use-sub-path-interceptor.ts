/**
 * Intercepts all Inertia navigation requests to automatically prefix them
 * with the deployment subdirectory path (e.g. /ekp).
 *
 * This fixes the "Page not found" errors that occur when the app is deployed
 * as a subdirectory alongside another application on the same domain.
 *
 * Usage: Call setupSubPathInterceptor() once during app initialization.
 */

const SUBPATH = '/ekp'; // Change this if your deployment path differs

export function setupSubPathInterceptor(): void {
    if (typeof window === 'undefined') return;
    const inertiaInterceptors = (window as any).__inertia_interceptors__;
    if (!inertiaInterceptors?.onVisitRequest) return;

    // Only apply if we're actually running under a subdirectory
    if (!window.location.pathname.startsWith(SUBPATH)) return;

    inertiaInterceptors.onVisitRequest(async (_visit: any, config: any) => {
        // Skip if this is already an external/full URL
        if (config.url?.startsWith('http://') || config.url?.startsWith('https://')) {
            return config;
        }

        // Prepend subpath to absolute paths starting with '/'
        if (config.url?.startsWith('/') && !config.url.startsWith('//')) {
            config.url = SUBPATH + config.url;
        }

        return config;
    });
}
