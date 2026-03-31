# 🔧 Solução para Erro CORS no Firebase Storage

## Problema Identificado

Você está recebendo erros CORS ao tentar:
- ✗ Enviar imagens/arquivos
- ✗ Enviar mensagens no mobile
- ✗ Acessar grupos no mobile

**Erro no console:**
```
Access to XMLHttpRequest at 'https://firebasestorage.googleapis.com/v0/b/crm-quimbanda-chat.firebasestorage.app/o/...' 
from origin 'https://crm.quimbanda.jp' has been blocked by CORS policy
```

## Causa Raiz

O Firebase Storage **não autoriza requisições** da origem `https://crm.quimbanda.jp`. Há três soluções:

---

## ✅ Solução 1: Atualizar Regras de Segurança do Firebase Storage (RECOMENDADO)

### Passo 1: Acessar Firebase Console
1. Vá para [https://console.firebase.google.com](https://console.firebase.google.com)
2. Selecione o projeto **`crm-quimbanda-chat`**

### Passo 2: Ir para Storage Rules
1. No menu esquerdo, clique em **Storage** (ou **Cloud Storage**)
2. Clique na aba **Rules**

### Passo 3: Atualizar as Regras
Substitua as regras atuais por:

```rules
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    // Permitir leitura pública de arquivos do chat
    match /chat_uploads/{allPaths=**} {
      allow read;
      allow write: if request.auth != null;
    }
    
    // Caminho genérico - permitir operações autenticadas
    match /{allPaths=**} {
      allow read, write: if request.auth != null;
    }
  }
}
```

### Passo 4: Publicar as Regras
- Clique no botão **Publish** (azul)
- Aguarde a confirmação

---

## ✅ Solução 2: Configurar CORS no Bucket (Se usar Google Cloud Storage)

Se as regras acima não resolverem, você pode configurar CORS diretamente:

### Passo 1: Instalar Google Cloud SDK
```bash
# Windows PowerShell
# Baixar de: https://cloud.google.com/sdk/docs/install-windows

# Ou via Chocolatey:
choco install google-cloud-sdk
```

### Passo 2: Autenticar com Google Cloud
```bash
gcloud auth login
gcloud config set project crm-quimbanda-chat
```

### Passo 3: Criar arquivo de configuração CORS
Crie um arquivo `cors.json`:

```json
[
  {
    "origin": ["https://crm.quimbanda.jp", "http://localhost:*"],
    "method": ["GET", "HEAD", "DELETE", "POST", "PUT", "OPTIONS"],
    "responseHeader": ["Content-Type", "x-goog-meta-*"],
    "maxAgeSeconds": 3600
  }
]
```

### Passo 4: Aplicar CORS ao bucket
```bash
gsutil cors set cors.json gs://crm-quimbanda-chat.appspot.com
```

---

## ✅ Solução 3: Usar um Proxy/Backend para Upload

Se as soluções acima não funcionarem, você pode fazer upload pelo backend PHP:

Adicione este endpoint em `api/upload.php`:

```php
<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $file = $_FILES['file'] ?? null;
    if (!$file) {
        throw new Exception('Nenhum arquivo enviado');
    }

    // Validar arquivo
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Arquivo muito grande');
    }

    // Criar path
    $uploadDir = __DIR__ . '/../storage/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Erro ao salvar arquivo');
    }

    // Retornar URL do arquivo
    $fileUrl = 'https://crm.quimbanda.jp/storage/uploads/' . $filename;

    echo json_encode([
        'success' => true,
        'url' => $fileUrl,
        'filename' => $filename,
        'size' => $file['size'],
        'type' => $file['type']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

---

## 🔍 Como Verificar se o Problema Foi Resolvido

### No Console do Navegador:
1. Abra DevTools (F12)
2. Vá para **Console**
3. Tente enviar uma imagem
4. Procure por mensagens verdes de sucesso:
   - `✅ Upload completo, URL: ...`
   - **SEM** erros de CORS em vermelho

### Na Aba Network:
1. Abra DevTools (F12)
2. Vá para **Network**
3. Envie uma imagem
4. Procure por requisições a `firebasestorage.googleapis.com`
5. Status deve ser **200** (verde), não CORS error

---

## 📱 Problemas no Mobile

O mobile pode ter problemas adicionais:

### Problema 1: Timeout de Conexão
Adicione um timeout maior em `tw-scripts.php`:

```javascript
// Aumentar timeout para upload em mobile
const uploadTask = f.uploadBytesResumable(storageRef, fileOrBlob, {
  contentType: fileOrBlob.type || 'application/octet-stream',
  customMetadata: {
    'uploadedFrom': navigator.userAgent.includes('Mobile') ? 'mobile' : 'desktop'
  }
});
```

### Problema 2: Conexão Intermitente
Adicione retry automático ao upload:

```javascript
async function uploadWithRetry(fileOrBlob, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      return await uploadAndSendFile(fileOrBlob);
    } catch (e) {
      console.warn(`[Upload] Tentativa ${attempt} falhou:`, e.message);
      if (attempt === maxRetries) throw e;
      await new Promise(r => setTimeout(r, 1000 * attempt));
    }
  }
}
```

---

## 🆘 Se Nada Funcionar

1. **Limpe o cache do navegador:**
   - Windows: Ctrl + Shift + Delete
   - Mac: Cmd + Shift + Delete

2. **Verifique a origem exata:**
   - Abra DevTools → Console
   - Execute: `console.log(window.location.origin)`
   - Certifique-se que é `https://crm.quimbanda.jp`

3. **Contate o suporte Firebase:**
   - Vá para [Firebase Support](https://firebase.google.com/support)
   - Mencione o erro CORS específico

---

## 📋 Checklist

- [ ] Acessei Firebase Console
- [ ] Atualizei as Storage Rules
- [ ] Cliquei em Publish
- [ ] Limpei o cache do navegador (Ctrl+Shift+Delete)
- [ ] Testei envio de imagem novamente
- [ ] Nenhum erro CORS no Console

**Se tudo estiver verde após esses passos, o problema foi resolvido! ✅**
