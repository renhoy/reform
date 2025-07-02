<?php
// {"_META_file_path_": "refor/components/header.php"}
// Componente de cabecera reutilizable

$currentPage = basename($_SERVER['REQUEST_URI']);
$navItems = [
    'dashboard' => ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => '🏠'],
    'tariffs' => ['title' => 'Tarifas', 'url' => 'tariffs.php', 'icon' => '📊'],
    'budgets' => ['title' => 'Presupuestos', 'url' => 'budgets.php', 'icon' => '📋']
];

function isActiveNav($key, $activeNav = null, $currentPage = null) {
    if ($activeNav) {
        return $activeNav === $key;
    }
    
    global $navItems;
    return $currentPage === $navItems[$key]['url'];
}
?>

<header class="header">
    <div class="header__container">
        <div class="header__brand">
            <a href="dashboard.php" class="brand">
                <div class="brand__icon">💰</div>
                <div class="brand__text">
                    <div class="brand__title">Budget Generator</div>
                    <div class="brand__subtitle">Generador de Presupuestos</div>
                </div>
            </a>
        </div>

        <nav class="header__nav">
            <ul class="nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <li class="nav__item">
                        <a href="<?= $item['url'] ?>" 
                           class="nav__link <?= isActiveNav($key, $activeNav ?? null, $currentPage) ? 'nav__link--active' : '' ?>">
                            <span class="nav__icon"><?= $item['icon'] ?></span>
                            <span class="nav__text"><?= $item['title'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header__actions">
            <div class="user-menu">
                <button class="user-menu__trigger" onclick="toggleUserMenu()">
                    <div class="user-menu__avatar">
                        <span class="user-menu__initial"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <div class="user-menu__info">
                        <div class="user-menu__name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></div>
                        <div class="user-menu__email"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                    </div>
                    <svg class="user-menu__chevron" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4.5 6L8 9.5L11.5 6"/>
                    </svg>
                </button>
                
                <div class="user-menu__dropdown" id="userMenuDropdown">
                    <div class="user-menu__item">
                        <a href="profile.php" class="user-menu__link">
                            <span class="user-menu__link-icon">👤</span>
                            Perfil
                        </a>
                    </div>
                    <div class="user-menu__item">
                        <a href="settings.php" class="user-menu__link">
                            <span class="user-menu__link-icon">⚙️</span>
                            Configuración
                        </a>
                    </div>
                    <div class="user-menu__divider"></div>
                    <div class="user-menu__item">
                        <a href="logout.php" class="user-menu__link user-menu__link--danger">
                            <span class="user-menu__link-icon">🚪</span>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    dropdown.classList.toggle('user-menu__dropdown--open');
}

// Cerrar menú al hacer clic fuera
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    
    if (!userMenu.contains(event.target)) {
        dropdown.classList.remove('user-menu__dropdown--open');
    }
});
</script>