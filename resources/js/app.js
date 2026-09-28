import './bootstrap';

import Alpine from 'alpinejs';
import { initializeNavigation, initializeDialogs } from './navigation';
import { toggleMovement } from './movements';

window.Alpine = Alpine;

// Keep requests from an older tab tied to the parking shown in that tab.
const parkingContext = document.querySelector('meta[name="parking-context"]')?.content;
if (parkingContext !== undefined) {
    window.axios.defaults.headers.common['X-Parking-Context'] = parkingContext;
    const originalFetch = window.fetch.bind(window);
    window.fetch = (input, init = {}) => {
        const url = new URL(input instanceof Request ? input.url : input, window.location.href);
        if (url.origin === window.location.origin) {
            const headers = new Headers(init.headers ?? (input instanceof Request ? input.headers : undefined));
            headers.set('X-Parking-Context', parkingContext);
            init = { ...init, headers };
        }
        return originalFetch(input, init);
    };
}

initializeNavigation();
initializeDialogs();
window.toggleMovement = toggleMovement;
Alpine.start();
