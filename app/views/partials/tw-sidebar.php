<?php
// $activePage - current page identifier (string): 'dashboard', 'atendimentos',
//   'trabalhos', 'clientes', 'filhos', 'quimbandeiro', 'mensalidades', 'giras',
//   'servicos', 'financeiro', 'usuarios', 'relatorios', 'configuracoes'

$navItems = [
  ['page' => 'dashboard',     'href' => 'dashboard.php',     'icon' => 'fa-chart-line',   'label' => 'Dashboard'],
  ['page' => 'atendimentos',  'href' => 'atendimentos.php',  'icon' => 'fa-headset',      'label' => 'Atendimentos'],
  ['page' => 'trabalhos',     'href' => 'trabalhos.php',     'icon' => 'fa-briefcase',    'label' => 'Trabalhos'],
  ['page' => 'clientes',      'href' => 'clientes.php',      'icon' => 'fa-users',        'label' => 'Clientes'],
  ['page' => 'filhos',        'href' => 'filhos.php',        'icon' => 'fa-people-group', 'label' => 'Filhos'],
  ['page' => 'quimbandeiro',  'href' => 'quimbandeiro.php',  'icon' => 'fa-fire',         'label' => 'Quimbandeiro'],
  ['page' => 'mensalidades',  'href' => 'mensalidades.php',  'icon' => 'fa-coins',        'label' => 'Mensalidades'],
  ['page' => 'giras',         'href' => 'giras.php',         'icon' => 'fa-drum',         'label' => 'Registro de Giras'],
  ['page' => 'servicos',      'href' => 'servicos.php',      'icon' => 'fa-tags',         'label' => 'Serviços'],
  ['page' => 'financeiro',    'href' => 'financeiro.php',    'icon' => 'fa-wallet',       'label' => 'Financeiro'],
  ['page' => 'usuarios',      'href' => 'usuarios.php',      'icon' => 'fa-user-shield',  'label' => 'Usuários'],
  ['page' => 'relatorios',    'href' => 'relatorios.php',    'icon' => 'fa-file-lines',   'label' => 'Relatórios'],
  ['page' => 'configuracoes', 'href' => 'configuracoes.php', 'icon' => 'fa-gear',         'label' => 'Configurações'],
];
$active = $activePage ?? '';
?>
<!-- Mobile hamburger button (fixed, visible only on small screens) -->
<button id="sidebarOpen" class="md:hidden fixed top-4 left-4 z-50 h-10 w-10 rounded-xl bg-black text-red-500 flex items-center justify-center shadow-lg">
  <i class="fa-solid fa-bars text-lg"></i>
</button>

<!-- Overlay (mobile only) -->
<div id="sidebarOverlay" class="md:hidden fixed inset-0 bg-black/50 z-40 hidden"></div>

<!-- Sidebar: hidden off-screen on mobile, static on md+ -->
<aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-40 w-64 bg-black border-r border-red-900 p-6 flex flex-col min-h-screen max-h-screen overflow-hidden transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out">
  <div class="flex items-center gap-3 shrink-0">
    <div id="brandLogo" class="h-10 w-10 rounded-xl bg-red-900 flex items-center justify-center text-red-400 font-black shrink-0">CT</div>
    <div class="flex-1 min-w-0">
      <div class="text-xl font-black text-red-600 truncate" id="brandName">CRM Terreiro</div>
      <p class="text-gray-500 text-sm mt-1">SaaS Premium</p>
    </div>
    <button id="sidebarClose" class="md:hidden text-gray-400 hover:text-white shrink-0">
      <i class="fa-solid fa-xmark text-lg"></i>
    </button>
  </div>
  <nav class="mt-8 space-y-1 flex-1 overflow-y-auto scrollbar-thin">
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = ($active === $item['page']); ?>
      <a href="<?= htmlspecialchars($item['href']) ?>"
         class="flex items-center gap-3 px-3 py-2 rounded-lg font-bold transition-colors
                <?= $isActive ? 'bg-red-700 text-white' : 'text-gray-400 hover:bg-red-800 hover:text-white' ?>">
        <i class="fa-solid <?= $item['icon'] ?> w-4"></i> <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
    <a href="logout" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-900 font-bold transition-colors">
      <i class="fa-solid fa-right-from-bracket w-4"></i> Sair
    </a>
  </nav>
</aside>

<script>
(function(){
  var sb=document.getElementById('sidebar'),ov=document.getElementById('sidebarOverlay'),
      ob=document.getElementById('sidebarOpen'),cb=document.getElementById('sidebarClose');
  function open(){sb.classList.remove('-translate-x-full');sb.classList.add('translate-x-0');ov.classList.remove('hidden');}
  function close(){sb.classList.add('-translate-x-full');sb.classList.remove('translate-x-0');ov.classList.add('hidden');}
  if(ob)ob.addEventListener('click',open);
  if(cb)cb.addEventListener('click',close);
  if(ov)ov.addEventListener('click',close);
})();
</script>
