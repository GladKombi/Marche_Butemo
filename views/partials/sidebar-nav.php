<?php
$sidebarPage = basename($_SERVER['PHP_SELF'] ?? '');
$sidebarSection = $sidebarPage === 'commande-ajout.php' ? 'commandes.php' : $sidebarPage;
$sidebarRole = $user_type ?? ($_SESSION['user_type'] ?? 'acheteur');
$sidebarLink = static function ($href, $icon, $label, $section) use ($sidebarSection) {
    $active = $sidebarSection === $section;
    printf(
        '<a href="%s" class="nav-link%s"%s><i class="bi %s"></i> %s</a>',
        htmlspecialchars($href),
        $active ? ' active' : '',
        $active ? ' aria-current="page"' : '',
        htmlspecialchars($icon),
        htmlspecialchars($label)
    );
};
?>
<nav class="sidebar-nav" aria-label="Navigation principale">
    <span class="nav-label">Menu principal</span>
    <?php $sidebarLink('dashboard.php', 'bi-speedometer2', 'Tableau de bord', 'dashboard.php'); ?>

    <?php if (in_array($sidebarRole, ['admin', 'agriculteur'], true)): ?>
        <?php $sidebarLink('produits.php', 'bi-box-seam', 'Produits', 'produits.php'); ?>
    <?php endif; ?>

    <?php if (in_array($sidebarRole, ['admin', 'agriculteur'], true)): ?>
        <?php $sidebarLink('commandes.php', 'bi-cart3', 'Commandes', 'commandes.php'); ?>
    <?php endif; ?>

    <?php if ($sidebarRole === 'agriculteur'): ?>
        <?php $sidebarLink('paiements.php', 'bi-wallet2', 'Paiements', 'paiements.php'); ?>
    <?php endif; ?>

    <?php if ($sidebarRole === 'acheteur'): ?>
        <?php $sidebarLink('mes-commandes.php', 'bi-bag-check', 'Mes commandes', 'mes-commandes.php'); ?>
    <?php endif; ?>

    <?php if ($sidebarRole === 'livreur'): ?>
        <?php $sidebarLink('livraisons.php', 'bi-truck', 'Mes livraisons', 'livraisons.php'); ?>
    <?php endif; ?>

    <?php if ($sidebarRole === 'admin'): ?>
        <span class="nav-label">Administration</span>
        <?php $sidebarLink('utilisateurs.php', 'bi-people', 'Utilisateurs', 'utilisateurs.php'); ?>
    <?php endif; ?>
</nav>

<?php
// Le chat est disponible uniquement entre acheteurs et agriculteurs.
if (in_array($sidebarRole, ['acheteur', 'agriculteur'], true)) {
    require __DIR__ . '/chat-widget.php';
}
?>
