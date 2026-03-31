/* Firebase Messaging Service Worker */
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

// VAPID pública configurada no cliente (getToken), mantida aqui para referência:
// BL1UqUTCKB2I3bkJ8XKcUPI-l7vPIbY7qH_uKNq9AEl6KQGX5LDOYJ-qBjnoAso0HuaVbgVhkZ1eCUSsqSk9U5U

firebase.initializeApp({
  apiKey: 'AIzaSyCz1NVevA6aOCfIhGypLiV1ZqbyihV8SQw',
  authDomain: 'crm-quimbanda-chat.firebaseapp.com',
  projectId: 'crm-quimbanda-chat',
  storageBucket: 'crm-quimbanda-chat.firebasestorage.app',
  messagingSenderId: '675701723904',
  appId: '1:675701723904:web:1284b4169bf82b9e7c59e5',
  measurementId: 'G-QHKS3WVLJ5'
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const notificationTitle = (payload && payload.notification && payload.notification.title)
    ? payload.notification.title
    : 'CRM Terreiro';

  const notificationOptions = {
    body: (payload && payload.notification && payload.notification.body)
      ? payload.notification.body
      : 'Você recebeu uma nova mensagem.',
    icon: '/public/static/logo-quimbanda.png',
    badge: '/public/static/logo-quimbanda.png',
    data: payload?.data || {}
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil((async () => {
    const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of allClients) {
      if (client.url.includes('/chat.php') && 'focus' in client) {
        return client.focus();
      }
    }
    if (clients.openWindow) {
      return clients.openWindow('/chat.php');
    }
    return null;
  })());
});
