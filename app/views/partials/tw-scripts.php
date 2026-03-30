<?php
// Load external app.js (single source for formatBRL and helpers)
$_appJsPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/public/assets/js/app.js';
if (file_exists($_appJsPath)):
  $_appJsVer  = @filemtime($_appJsPath) ?: time();
  $_appJsHash = @md5_file($_appJsPath) ?: '';
?>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/app.js?v=<?= $_appJsVer ?>&h=<?= substr($_appJsHash, 0, 8) ?>"></script>
<?php endif; ?>

<script type="module">
import { initializeApp, getApps, getApp } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js";
import { getAnalytics, isSupported as analyticsIsSupported } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-analytics.js";
import {
  getFirestore,
  collection,
  doc,
  addDoc,
  setDoc,
  updateDoc,
  deleteDoc,
  query,
  where,
  orderBy,
  limit,
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

try {
  const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
  const db = getFirestore(app);
  const storage = getStorage(app);

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
    query,
    where,
    orderBy,
    limit,
    onSnapshot,
    serverTimestamp,
    Timestamp,
    ref,
    uploadBytesResumable,
    getDownloadURL
  };

  analyticsIsSupported().then((ok) => {
    if (!ok) return;
    try {
      const analytics = getAnalytics(app);
      window.firebaseAnalytics = analytics;
    } catch (_) {}
  }).catch(() => {});

  console.log('🔥 Firebase Iniciado com Sucesso');
  window.dispatchEvent(new CustomEvent('firebase-ready'));
} catch (e) {
  console.error('Falha ao inicializar Firebase:', e);
}
</script>
