import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

Pusher.logToConsole = true;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
    enabledTransports: ["ws", "wss"],
    
    authorizer: (channel, options) => {
    return {
      authorize: (socketId, callback) => {
        window.axios.post('/broadcasting/auth', {
          socket_id: socketId,
          channel_name: channel.name,
        }).then(response => {
          callback(false, response.data);
        }).catch(error => {
          console.error('[Echo auth error]', error?.response?.data || error);
          callback(true, error);
        });
      }
    };
  }
});
