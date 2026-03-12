<?php
// $pageTitle - page title (string)
// $extraHead - optional extra head content (string)
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'CRM Terreiro') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet" />
  <!-- Use compiled Tailwind if available, otherwise CDN -->
  <?php if (file_exists(__DIR__ . '/../../../public/assets/css/app.css')): ?>
  <link rel="stylesheet" href="public/assets/css/app.css" />
  <?php else: ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: { accent: '#dc2626' },
        },
      },
    };
  </script>
  <?php endif; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <?= $extraHead ?? '' ?>
</head>
