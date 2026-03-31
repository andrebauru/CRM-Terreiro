<script type="module">
import { initializeApp, getApps, getApp } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js";
import { getAnalytics, isSupported as analyticsIsSupported } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-analytics.js";
import { getMessaging, getToken, onMessage, isSupported as messagingIsSupported } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging.js";
import {
  getFirestore,
  collection,
  doc,
  addDoc,
  setDoc,
  updateDoc,
  deleteDoc,
  getDocs,
  query,
  where,
  orderBy,
  limit,
  limitToLast,
  onSnapshot,
  serverTimestamp,
  Timestamp
} from "https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js";
import {
  getStorage,
  ref,
  uploadBytesResumable,
  getDownloadURL
} from "https://www.gstatic.com/firebasejs/10.12.5/firebase-storage.js";

const firebaseConfig = {
  apiKey: "AIzaSyCz1NVevA6aOCfIhGypLiV1ZqbyihV8SQw",
  authDomain: "crm-quimbanda-chat.firebaseapp.com",
  projectId: "crm-quimbanda-chat",
  storageBucket: "crm-quimbanda-chat.firebasestorage.app",
  messagingSenderId: "675701723904",
  appId: "1:675701723904:web:1284b4169bf82b9e7c59e5",
  measurementId: "G-QHKS3WVLJ5"
};

// Chave VAPID pública para Firebase Cloud Messaging Web Push
window.__FCM_VAPID_KEY = "BL1UqUTCKB2I3bkJ8XKcUPI-l7vPIbY7qH_uKNq9AEl6KQGX5LDOYJ-qBjnoAso0HuaVbgVhkZ1eCUSsqSk9U5U";

try {
  const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
  // Firestore explícito com SDK v10
  const db = getFirestore(app);
  const storage = getStorage(app);
  let messaging = null;

  messagingIsSupported().then((supported) => {
    window.firebaseMessagingSupported = supported;
    if (!supported) return;
    try {
      messaging = getMessaging(app);
      window.firebaseMessaging = messaging;
      if (window.firebaseContext) window.firebaseContext.messaging = messaging;
    } catch (err) {
      console.warn('FCM indisponível neste ambiente:', err);
    }
  }).catch(() => {});

  window.firebaseApp = app;
  window.db = db;
  window.storage = storage;
  window.firebaseFns = {
    collection,
    doc,
    addDoc,
    setDoc,
    updateDoc,
    deleteDoc,
    getDocs,
    query,
    where,
    orderBy,
    limit,
    limitToLast,
    onSnapshot,
    serverTimestamp,
    Timestamp,
    getToken,
    onMessage,
    ref,
    uploadBytesResumable,
    getDownloadURL
  };
  window.firebaseContext = {
    app,
    db,
    storage,
    messaging,
    fns: window.firebaseFns,
  };

  analyticsIsSupported().then((ok) => {
    if (!ok) return;
    try {
      const analytics = getAnalytics(app);
      window.firebaseAnalytics = analytics;
    } catch (_) {}
  }).catch(() => {});

  window.firebaseReady = true;
  console.log('🔥 Firebase Iniciado com Sucesso (Firestore via getFirestore v10)');
  window.dispatchEvent(new CustomEvent('firebase-ready'));
} catch (e) {
  window.firebaseReady = false;
  console.error('Falha ao inicializar Firebase:', e);
}
</script>
