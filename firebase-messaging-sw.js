/* Firebase Messaging Service Worker */
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

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
  const title = payload?.notification?.title || 'CRM Terreiro';
  const options = {
    body: payload?.notification?.body || 'Você recebeu uma nova mensagem.',
    icon: '/public/static/logo-quimbanda.png',
    badge: '/public/static/logo-quimbanda.png',
    data: payload?.data || {}
  };

  self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow('/chat.php'));
});
