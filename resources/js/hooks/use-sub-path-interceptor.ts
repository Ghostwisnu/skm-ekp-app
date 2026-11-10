/**
 * Intercepts all Inertia navigation and form submissions to automatically
 * prefix them with the deployment subdirectory path (e.g. /ekp).
 *
 * This fixes URL collision when deployed as a subdirectory alongside another
 * application on the same domain (e.g. inventoryai.id/ekp/ vs inventoryai.id/).
 *
 * Usage: Call setupSubPathInterceptor() before createInertiaApp().
 */

import { router } from '@inertiajs/react';

// Deploy subdirectory path — must match the actual deployment path
const SUBPATH = '/ekp';

/**
 * Prepends the subpath to an absolute URL if needed.
 * - '/login' → '/ekp/login' (when running under /ekp/)
 * - 'login'  → 'login' (relative URLs stay unchanged)
 * - 'https://...' → unchanged (external URLs)
 */
function prefixUrl(url: string): string {
    if (!url) return url;
    // Skip external URLs
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    // Skip already-prefixed URLs
    if (url.startsWith(SUBPATH)) return url;
    // Prefix absolute paths
    if (url.startsWith('/') && !url.startsWith('//')) {
        return SUBPATH + url;
    }
    return url;
}

export function setupSubPathInterceptor(): void {
    if (typeof window === 'undefined') return;

    // Only activate when running under the deployment subdirectory
    if (!window.location.pathname.startsWith(SUBPATH)) return;

    // ── Monkey-patch Inertia router.visit ────────────────────────────────
    // This intercepts ALL Link clicks, router.get/post/put/delete calls,
    // AND Form component submissions (since Inertia's Form uses router.visit internally).
    const originalVisit = router.visit.bind(router);
    router.visit = function visit(href: any, options: any = {}): void {
        // Resolve the href to a string URL
        let url: string;
        if (typeof href === 'string') {
            url = href;
        } else if (href && typeof href === 'object' && 'url' in href) {
            url = (href as any).url;
        } else {
            return originalVisit(href, options);
        }
        // Prefix the URL with subpath
        const prefixedUrl = prefixUrl(url);
        if (prefixedUrl !== url) {
            if (typeof href === 'string') {
                return originalVisit(prefixedUrl, options);
            }
            return originalVisit({ ...href, url: prefixedUrl }, options);
        }
        return originalVisit(href, options);
    };
    // Preserve the original toString for debugging
    Object.defineProperty(router.visit, 'toString', {
        value: () => originalVisit.toString(),
        writable: false,
        configurable: true,
    });

    // ── Fix HTML form actions via MutationObserver ────────────────────────
    // This catches forms added dynamically (including the initial page load)
    // and rewrites their action attribute to include the subpath prefix.
    const fixFormActions = (container: Element | null = document.body): void => {
        if (!container) return;
        container.querySelectorAll<HTMLFormElement>('form[action]').forEach((form) => {
            const action = form.getAttribute('action');
            if (action) {
                const fixed = prefixUrl(action);
                if (fixed !== action) {
                    form.setAttribute('action', fixed);
                }
            }
        });
    };

    // Run once immediately for existing forms
    fixFormActions();

    // Watch for new forms added to the DOM (Inertia page transitions add forms)
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node instanceof HTMLElement) {
                    fixFormActions(node);
                }
            }
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
}
