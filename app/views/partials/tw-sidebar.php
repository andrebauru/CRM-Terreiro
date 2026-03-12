<?php
// $activePage - current page identifier (string): 'dashboard', 'filhos', 'quimbandeiro',
//   'mensalidades', 'clientes', 'trabalhos', 'financeiro', 'usuarios', 'atendimentos',
//   'relatorios', 'configuracoes'

$navItems = [
  ['page' => 'dashboard',     'href' => 'dashboard.php',     'icon' => 'fa-chart-line',   'label' => 'Dashboard'],
  ['page' => 'filhos',        'href' => 'filhos.php',        'icon' => 'fa-people-group', 'label' => 'Filhos'],
  ['page' => 'quimbandeiro',  'href' => 'quimbandeiro.php',  'icon' => 'fa-fire',         'label' => 'Quimbandeiro'],
  ['page' => 'mensalidades',  'href' => 'mensalidades.php',  'icon' => 'fa-coins',        'label' => 'Mensalidades'],
  ['page' => 'clientes',      'href' => 'clientes.php',      'icon' => 'fa-users',        'label' => 'Clientes'],
  ['page' => 'trabalhos',     'href' => 'trabalhos.php',     'icon' => 'fa-briefcase',    'label' => 'Trabalhos'],
  ['page' => 'financeiro',    'href' => 'financeiro.php',    'icon' => 'fa-wallet',       'label' => 'Financeiro'],
  ['page' => 'usuarios',      'href' => 'usuarios.php',      'icon' => 'fa-user-shield',  'label' => 'Usuários'],
  ['page' => 'atendimentos',  'href' => 'atendimentos.php',  'icon' => 'fa-headset',      'label' => 'Atendimentos'],
  ['page' => 'relatorios',    'href' => 'relatorios.php',    'icon' => 'fa-file-lines',   'label' => 'Relatórios'],
  ['page' => 'configuracoes', 'href' => 'configuracoes.php', 'icon' => 'fa-gear',         'label' => 'Configurações'],
];
$active = $activePage ?? '';
?>
<aside class="w-64 bg-black border-r border-red-900 p-6 flex flex-col">
  <div class="flex items-center gap-3">
    <div id="brandLogo" class="h-10 w-10 rounded-xl bg-red-900 flex items-center justify-center text-red-400 font-black">CT</div>
    <div>
      <div class="text-xl font-black text-red-600" id="brandName">CRM Terreiro</div>
      <p class="text-gray-500 text-sm mt-1">SaaS Premium</p>
    </div>
  </div>
  <nav class="mt-8 space-y-1 flex-1">
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = ($active === $item['page']); ?>
      <a href="<?= htmlspecialchars($item['href']) ?>"
         class="flex items-center gap-3 px-3 py-2 rounded-lg font-bold transition-colors
                <?= $isActive ? 'bg-red-700 text-white' : 'text-gray-400 hover:bg-red-800 hover:text-white' ?>">
        <i class="fa-solid <?= $item['icon'] ?> w-4"></i> <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
    <a href="index.html" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-900 font-bold transition-colors">
      <i class="fa-solid fa-right-from-bracket w-4"></i> Sair
    </a>
  </nav>
</aside>
