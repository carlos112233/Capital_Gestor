import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: window.location.hostname,
            wsPort: window.location.port || 80,
            wssPort: window.location.port || 443,
            forceTLS: window.location.protocol === 'https:',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (e) {
        console.warn('Laravel Echo no se inicializó:', e);
    }
}
