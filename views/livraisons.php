<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in'])) { header('Location: ../connexion.php'); exit; }
if (($_SESSION['user_type'] ?? '') !== 'livreur') { header('Location: dashboard.php'); exit; }

require_once __DIR__ . '/../models/select/LivraisonSelect.php';
$user_id = (int) $_SESSION['user_id'];
$user_type = 'livreur';
$user_nom = $_SESSION['user_nom'] ?? '';
$user_prenom = $_SESSION['user_prenom'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$userInitials = strtoupper(substr($user_nom, 0, 1) . substr($user_prenom, 0, 1));
$livraisons = LivraisonSelect::getByLivreur($user_id);
$stats = ['total' => count($livraisons), 'a_demarrer' => 0, 'en_route' => 0, 'terminees' => 0];
foreach ($livraisons as $l) {
    if ($l->statut_livraison === 'terminee') $stats['terminees']++;
    elseif ($l->statut_livraison === 'en_cours' && $l->date_depart) $stats['en_route']++;
    elseif ($l->statut_livraison === 'en_cours') $stats['a_demarrer']++;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Mes livraisons - Butembo Marché</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--green:#2d6a4f;--bg:#f5f7f6;--surface:#fff;--text:#17202a;--border:#e2e8f0;--muted:#64748b;--soft:#f8fafc}html.dark{--bg:#0f172a;--surface:#1e293b;--text:#e2e8f0;--border:#334155;--muted:#94a3b8;--soft:#0f172a}*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Inter,system-ui,sans-serif;color:var(--text);transition:background .2s,color .2s}.layout{display:flex;min-height:100vh}.sidebar{width:260px;background:var(--surface);border-right:1px solid var(--border);padding:24px 16px;position:fixed;inset:0 auto 0 0;display:flex;flex-direction:column}.brand{font-size:1.15rem;font-weight:800;color:var(--green);padding:0 12px 24px}.sidebar-nav{display:flex;flex-direction:column;gap:6px}.nav-label{padding:12px;color:#94a3b8;font-size:.72rem;text-transform:uppercase;font-weight:700}.nav-link{display:flex;gap:12px;align-items:center;padding:11px 13px;border-radius:10px;color:var(--muted);text-decoration:none}.nav-link.active,.nav-link:hover{background:var(--green);color:#fff}.profile{margin-top:auto;border-top:1px solid var(--border);padding:16px 8px 0}.profile strong,.profile small{display:block}.profile small{color:var(--muted)}.main{margin-left:260px;width:calc(100% - 260px);padding:28px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.top-actions{display:flex;gap:8px}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.card-stat,.panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px}.card-stat strong{display:block;font-size:1.7rem}.card-stat span{color:var(--muted)}.delivery{border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:14px}.delivery-head{display:flex;justify-content:space-between;gap:12px}.meta{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:16px 0}.label{font-size:.75rem;text-transform:uppercase;color:#94a3b8;font-weight:700}.products{background:var(--soft);border-radius:9px;padding:12px}.badge-status{padding:6px 10px;border-radius:20px;font-size:.78rem;font-weight:700}.waiting{background:#fef3c7;color:#92400e}.route{background:#dbeafe;color:#1d4ed8}.done{background:#dcfce7;color:#166534}.cancelled{background:#fee2e2;color:#991b1b}html.dark .btn-light{background:#334155;border-color:#475569;color:#e2e8f0}html.dark .text-secondary{color:#94a3b8!important}@media(max-width:850px){.sidebar{position:static;width:100%;height:auto}.layout{display:block}.main{margin:0;width:100%;padding:16px}.cards,.meta{grid-template-columns:1fr 1fr}}@media(max-width:520px){.cards,.meta{grid-template-columns:1fr}.delivery-head{display:block}}
        .confirm-overlay{position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;visibility:hidden;transition:.2s;backdrop-filter:blur(3px)}.confirm-overlay.active{opacity:1;visibility:visible}.confirm-modal{width:100%;max-width:440px;background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.2);padding:26px;transform:translateY(15px);transition:.2s}.confirm-overlay.active .confirm-modal{transform:none}.confirm-icon{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;background:#e8f5ee;color:var(--green);font-size:1.5rem;margin-bottom:16px}.confirm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}
    </style>
</head>
<body><div class="layout">
<aside class="sidebar"><div class="brand"><i class="bi bi-basket2-fill"></i> Butembo Marché</div>
<?php require __DIR__ . '/partials/sidebar-nav.php'; ?>
<div class="profile"><strong><?= htmlspecialchars($user_prenom . ' ' . $user_nom) ?></strong><small><?= htmlspecialchars($user_email) ?></small><a class="btn btn-sm btn-outline-danger mt-3" href="../models/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></div></aside>
<main class="main"><div class="top"><div><h1 class="h3 mb-1">Mes livraisons</h1><p class="text-secondary mb-0">Commandes qui vous ont été assignées</p></div><div class="top-actions"><button class="btn btn-outline-secondary" type="button" onclick="toggleDarkMode()" title="Mode sombre" aria-label="Basculer le mode sombre"><i class="bi bi-moon-stars" id="themeIcon"></i></button><button class="btn btn-outline-success" type="button" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button></div></div>
<section class="cards"><div class="card-stat"><strong><?= $stats['total'] ?></strong><span>Total</span></div><div class="card-stat"><strong><?= $stats['a_demarrer'] ?></strong><span>À démarrer</span></div><div class="card-stat"><strong><?= $stats['en_route'] ?></strong><span>En route</span></div><div class="card-stat"><strong><?= $stats['terminees'] ?></strong><span>Terminées</span></div></section>
<section class="panel"><div id="message"></div>
<?php if (!$livraisons): ?><div class="text-center py-5 text-secondary"><i class="bi bi-truck fs-1 d-block mb-2"></i>Aucune livraison ne vous est assignée.</div><?php endif; ?>
<?php foreach ($livraisons as $l): $started = !empty($l->date_depart); ?>
<article class="delivery"><div class="delivery-head"><div><strong>#<?= htmlspecialchars($l->numero_commande) ?></strong><div class="text-secondary small">Suivi : <?= htmlspecialchars($l->code_suivi ?: '—') ?></div></div><span class="badge-status <?= $l->statut_livraison === 'terminee' ? 'done' : ($l->statut_livraison === 'annulee' ? 'cancelled' : ($started ? 'route' : 'waiting')) ?>"><?= $l->statut_livraison === 'terminee' ? 'Livrée' : ($l->statut_livraison === 'annulee' ? 'Annulée' : ($started ? 'En route' : 'À démarrer')) ?></span></div>
<div class="meta"><div><div class="label">Client</div><strong><?= htmlspecialchars($l->client_prenom . ' ' . $l->client_nom) ?></strong><div><?= htmlspecialchars($l->client_telephone ?: 'Téléphone non renseigné') ?></div></div><div><div class="label">Adresse</div><strong><?= htmlspecialchars($l->adresse_livraison ?: 'Non renseignée') ?></strong><div><?= htmlspecialchars($l->instructions ?: '') ?></div></div><div><div class="label">Assignée le</div><strong><?= date('d/m/Y H:i', strtotime($l->date_assignation)) ?></strong><div><?= number_format((float)$l->montant_total, 0, ',', ' ') ?> FC</div></div></div>
<div class="products mb-3"><div class="label mb-1">Produits</div><?= htmlspecialchars(str_replace('||', ' • ', $l->produits ?: 'Aucun produit')) ?></div>
<?php if ($l->statut_livraison === 'en_cours'): ?><button class="btn <?= $started ? 'btn-success' : 'btn-primary' ?>" onclick="openConfirmModal(<?= (int)$l->id ?>,'<?= $started ? 'complete' : 'start' ?>',this,'<?= htmlspecialchars($l->numero_commande, ENT_QUOTES, 'UTF-8') ?>')"><i class="bi <?= $started ? 'bi-check-circle' : 'bi-play-circle' ?> me-1"></i><?= $started ? 'Marquer comme livrée' : 'Démarrer la livraison' ?></button><?php endif; ?>
</article><?php endforeach; ?></section></main></div>
<div class="confirm-overlay" id="confirmModal" aria-hidden="true"><div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle"><div class="confirm-icon"><i class="bi bi-question-lg"></i></div><h3 class="h5" id="confirmTitle">Confirmer l’action</h3><p class="text-secondary mb-0" id="confirmText"></p><div class="confirm-actions"><button type="button" class="btn btn-light" onclick="closeConfirmModal()">Annuler</button><button type="button" class="btn btn-success" id="confirmActionButton" onclick="confirmDeliveryAction()">Confirmer</button></div></div></div>
<script>
function applyThemeIcon(){const icon=document.getElementById('themeIcon');if(icon)icon.className=document.documentElement.classList.contains('dark')?'bi bi-sun':'bi bi-moon-stars'}
function toggleDarkMode(){document.documentElement.classList.toggle('dark');localStorage.setItem('theme',document.documentElement.classList.contains('dark')?'dark':'light');applyThemeIcon()}
if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}applyThemeIcon();
let pendingDelivery=null;
function openConfirmModal(id,action,button,numero){pendingDelivery={id,action,button};const complete=action==='complete';document.getElementById('confirmTitle').textContent=complete?'Confirmer la livraison':'Démarrer la livraison';document.getElementById('confirmText').textContent=complete?'Confirmez-vous que la commande #'+numero+' a bien été remise au client ?':'Voulez-vous démarrer la livraison de la commande #'+numero+' ?';const actionButton=document.getElementById('confirmActionButton');actionButton.disabled=false;actionButton.textContent=complete?'Oui, commande livrée':'Oui, démarrer';actionButton.className='btn '+(complete?'btn-success':'btn-primary');const modal=document.getElementById('confirmModal');modal.classList.add('active');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden'}
function closeConfirmModal(){const modal=document.getElementById('confirmModal');modal.classList.remove('active');modal.setAttribute('aria-hidden','true');document.body.style.overflow='';pendingDelivery=null}
document.getElementById('confirmModal').addEventListener('click',event=>{if(event.target===event.currentTarget)closeConfirmModal()});document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.getElementById('confirmModal').classList.contains('active'))closeConfirmModal()});
function confirmDeliveryAction(){if(!pendingDelivery)return;const data=pendingDelivery;document.getElementById('confirmActionButton').disabled=true;updateDelivery(data.id,data.action,data.button)}
async function updateDelivery(id,action,button){button.disabled=true;try{const response=await fetch('../models/traitement/livraisons-post.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,action})});const result=await response.json();if(!result.success)throw new Error(result.message);closeConfirmModal();document.getElementById('message').innerHTML='<div class="alert alert-success">'+result.message+'</div>';setTimeout(()=>location.reload(),700)}catch(error){document.getElementById('message').innerHTML='<div class="alert alert-danger">'+error.message+'</div>';button.disabled=false;closeConfirmModal()}}
</script>
</body></html>
