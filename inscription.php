<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['is_logged_in'])) { header('Location: index.php'); exit; }
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['register_csrf'])) $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
$error = '';
$values = ['prenom'=>'','nom'=>'','email'=>'','telephone'=>'','adresse'=>''];

function generateBuyerEmail(string $nom): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($nom));
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $ascii !== false ? $ascii : $nom));
    if ($base === '') $base = 'acheteur';
    $suffix = 1;
    do {
        $email = $base . ($suffix > 1 ? $suffix : '') . '@acheteur-bbomarcher.com';
        $suffix++;
    } while (fetchOne('SELECT id FROM utilisateurs WHERE email = :email LIMIT 1', [':email'=>$email]));
    return $email;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $value) $values[$key] = trim($_POST[$key] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    if (!hash_equals($_SESSION['register_csrf'], $_POST['csrf_token'] ?? '')) $error = 'La session du formulaire a expiré. Rechargez la page.';
    elseif (in_array('', [$values['prenom'],$values['nom'],$values['telephone'],$values['adresse']], true) || $password === '' || $confirmation === '') $error = 'Veuillez remplir tous les champs obligatoires.';
    elseif (mb_strlen($password) < 4) $error = 'Le mot de passe doit contenir au moins 4 caractères.';
    elseif ($password !== $confirmation) $error = 'Les mots de passe ne correspondent pas.';
    else {
        $email = generateBuyerEmail($values['nom']);
        {
            try {
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,telephone,mot_de_passe,type_utilisateur,adresse,est_verifie,statut,supprime) VALUES (:nom,:prenom,:email,:telephone,:password,'acheteur',:adresse,0,'actif',0)");
                $stmt->execute([':nom'=>$values['nom'],':prenom'=>$values['prenom'],':email'=>$email,':telephone'=>$values['telephone'],':password'=>password_hash($password,PASSWORD_DEFAULT),':adresse'=>$values['adresse']]);
                session_regenerate_id(true);
                $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['user_nom']=$values['nom']; $_SESSION['user_prenom']=$values['prenom']; $_SESSION['user_email']=$email; $_SESSION['user_telephone']=$values['telephone']; $_SESSION['user_adresse']=$values['adresse']; $_SESSION['user_type']='acheteur'; $_SESSION['user_photo']=null; $_SESSION['is_logged_in']=true; $_SESSION['login_time']=time(); $_SESSION['registration_email']=$email;
                unset($_SESSION['register_csrf']);
                header('Location: index.php?inscription=succes'); exit;
            } catch (Throwable $e) { error_log('Inscription acheteur : '.$e->getMessage()); $error='Impossible de créer votre compte pour le moment.'; }
        }
    }
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Inscription acheteur - Butembo Marché</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet"><style>
:root{--green:#2d6a4f;--dark:#1b4332;--bg:#f2f7f4}body{min-height:100vh;margin:0;background:linear-gradient(135deg,#e8f5ee,var(--bg));font-family:Inter,system-ui,sans-serif;display:grid;place-items:center;padding:24px}.register{width:100%;max-width:720px;background:#fff;border:1px solid #dce9e1;border-radius:22px;box-shadow:0 22px 60px rgba(27,67,50,.12);overflow:hidden}.head{padding:28px 32px 20px;background:linear-gradient(135deg,var(--green),var(--dark));color:#fff}.brand{font-weight:800;font-size:1.2rem}.body{padding:28px 32px}.form-control{border-radius:11px;padding:11px 13px}.form-control:focus{border-color:var(--green);box-shadow:0 0 0 .2rem rgba(45,106,79,.12)}.btn-register{width:100%;padding:12px;border:0;border-radius:11px;background:var(--green);color:#fff;font-weight:700}.btn-register:hover{background:var(--dark)}.password-wrap{position:relative}.password-wrap .form-control{padding-right:45px}.toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:none;color:#64748b}.required{color:#dc3545}@media(max-width:600px){.body,.head{padding:22px}.row>*{width:100%}}
</style></head><body><div class="register"><div class="head"><div class="brand"><i class="bi bi-basket2-fill me-2"></i>Butembo Marché</div><h1 class="h3 mt-4 mb-1">Créer un compte acheteur</h1><p class="mb-0 opacity-75">Commandez les produits frais des agriculteurs de Butembo.</p></div><div class="body">
<style>.col-md-6:has(> input[name="email"]){display:none!important}</style>
<div class="alert alert-info"><i class="bi bi-envelope-check me-2"></i>L’adresse email de connexion sera créée automatiquement à partir de votre nom.</div>
<?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" novalidate><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['register_csrf'])?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">Prénom <span class="required">*</span></label><input class="form-control" name="prenom" value="<?=htmlspecialchars($values['prenom'])?>" required maxlength="100"></div><div class="col-md-6"><label class="form-label">Nom <span class="required">*</span></label><input class="form-control" name="nom" value="<?=htmlspecialchars($values['nom'])?>" required maxlength="100"></div><div class="col-md-6"><label class="form-label">Email <span class="required">*</span></label><input type="email" class="form-control" name="email" value="<?=htmlspecialchars($values['email'])?>" required maxlength="255"></div><div class="col-md-6"><label class="form-label">Téléphone <span class="required">*</span></label><input type="tel" class="form-control" name="telephone" value="<?=htmlspecialchars($values['telephone'])?>" required maxlength="20"></div><div class="col-12"><label class="form-label">Adresse <span class="required">*</span></label><input class="form-control" name="adresse" value="<?=htmlspecialchars($values['adresse'])?>" required placeholder="Quartier, avenue, numéro"></div><div class="col-md-6"><label class="form-label">Mot de passe <span class="required">*</span></label><div class="password-wrap"><input type="password" class="form-control" id="password" name="mot_de_passe" required minlength="4"><button type="button" class="toggle" onclick="togglePassword('password',this)"><i class="bi bi-eye"></i></button></div><small class="text-muted">Au moins 4 caractères</small></div><div class="col-md-6"><label class="form-label">Confirmer <span class="required">*</span></label><div class="password-wrap"><input type="password" class="form-control" id="confirmation" name="confirmation" required minlength="4"><button type="button" class="toggle" onclick="togglePassword('confirmation',this)"><i class="bi bi-eye"></i></button></div></div><div class="col-12"><button class="btn-register" type="submit"><i class="bi bi-person-plus me-2"></i>Créer mon compte</button></div></div></form><div class="text-center mt-4 text-muted">Vous avez déjà un compte ? <a href="connexion.php" class="fw-semibold text-success">Se connecter</a></div><div class="text-center mt-2"><a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Retour à l’accueil</a></div></div></div><script>function togglePassword(id,button){const input=document.getElementById(id);input.type=input.type==='password'?'text':'password';button.querySelector('i').className=input.type==='password'?'bi bi-eye':'bi bi-eye-slash'}</script></body></html>
