import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (import.meta.env.VITE_REVERB_APP_KEY) {
    const forceTLS = (import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')) === 'https';
    const configuredPort = Number(import.meta.env.VITE_REVERB_PORT);

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: configuredPort || 80,
        wssPort: configuredPort || 443,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
}
