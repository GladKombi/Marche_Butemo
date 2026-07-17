<?php

/**
 * Page de connexion - Marché Numérique de Butembo
 * Interface de connexion à la racine du projet
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    // Rediriger selon le rôle
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: views/admin/dashboard.php');
            break;
        case 'agriculteur':
            header('Location: views/agriculteur/dashboard.php');
            break;
        case 'livreur':
            header('Location: views/livreur/dashboard.php');
            break;
        default:
            header('Location: index.php');
            break;
    }
    exit;
}

// Récupérer les messages d'erreur
$error = $_SESSION['login_error'] ?? '';
$email = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_error']);
unset($_SESSION['login_email']);

// Message de succès (déconnexion, inscription)
$success = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deconnecte':
            $success = 'Vous avez été déconnecté avec succès.';
            break;
        case 'inscription':
            $success = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Butembo Marché Numérique</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-green: #2d6a4f;
            --primary-green-light: #40916c;
            --primary-green-dark: #1b4332;
            --earth-brown: #8d6e4e;
            --cream: #f8f3ee;
            --warm-white: #fefcf9;
            --dark-text: #2d2a24;
            --shadow-lg: 0 8px 40px rgba(45, 42, 36, 0.12);
            --radius-md: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #f8f3ee 0%, #e8ddd2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            padding: 40px 35px;
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo .logo-icon {
            font-size: 3rem;
            color: var(--primary-green);
            background: var(--cream);
            padding: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 12px;
        }

        .login-logo h4 {
            font-weight: 700;
            color: var(--primary-green-dark);
            margin-bottom: 4px;
        }

        .login-logo p {
            color: #6b6259;
            font-size: 0.9rem;
            margin: 0;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e8e4df;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.1);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e8e4df;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #8a7f74;
            padding: 12px 14px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-login {
            background: var(--primary-green);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            background: var(--primary-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(45, 106, 79, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e8e4df;
        }

        .divider span {
            padding: 0 16px;
            color: #8a7f74;
            font-size: 0.85rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .link-forgot {
            color: var(--primary-green);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .link-forgot:hover {
            color: var(--primary-green-dark);
            text-decoration: underline;
        }

        .link-register {
            color: var(--primary-green);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .link-register:hover {
            color: var(--primary-green-dark);
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 16px;
        }

        .back-home a {
            color: #6b6259;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .back-home a:hover {
            color: var(--primary-green);
        }

        /* Role badges */
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .role-badge.admin {
            background: #dc3545;
            color: #fff;
        }

        .role-badge.agriculteur {
            background: #28a745;
            color: #fff;
        }

        .role-badge.livreur {
            background: #17a2b8;
            color: #fff;
        }

        .role-badge.acheteur {
            background: #6c757d;
            color: #fff;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 28px 20px;
            }

            .login-logo .logo-icon {
                font-size: 2.4rem;
                padding: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-card">

            <!-- Logo -->
            <div class="login-logo">
                <div class="logo-icon">
                    <i class="bi bi-basket2-fill"></i>
                </div>
                <h5>Butembo Marché</h5>
                <p>Connectez-vous à votre compte</p>

            </div>

            <!-- Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form method="POST" action="models/login.php">
                <input type="hidden" name="action" value="login">

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">Adresse email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                            class="form-control <?= (!empty($error)) ? 'is-invalid' : '' ?>"
                            id="email"
                            name="email"
                            placeholder="nom@domaine.com"
                            value="<?= htmlspecialchars($email) ?>"
                            required>
                    </div>
                    <div class="form-text small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Utilisez votre email de connexion
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                            class="form-control <?= (!empty($error)) ? 'is-invalid' : '' ?>"
                            id="password"
                            name="password"
                            placeholder="Votre mot de passe"
                            required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius: 0 12px 12px 0; border-left: none;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Bouton de connexion -->
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>ou</span>
            </div>

            <!-- Lien d'inscription -->
            <div class="text-center">
                <p class="small text-muted mb-0">
                    Pas encore de compte ?
                    <a href="inscription.php" class="link-register">Créer un compte</a>
                </p>
            </div>

            <!-- Retour à l'accueil -->
            <div class="back-home">
                <a href="index.php">
                    <i class="bi bi-arrow-left me-1"></i> Retour à l'accueil
                </a>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Pré-remplir l'email si le cookie existe
        document.addEventListener('DOMContentLoaded', function() {
            const savedEmail = getCookie('user_email');
            if (savedEmail) {
                document.getElementById('email').value = savedEmail;
                document.getElementById('remember').checked = true;
            }
        });

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;

            email.classList.remove('is-invalid');
            password.classList.remove('is-invalid');

            if (!email.value.trim()) {
                email.classList.add('is-invalid');
                isValid = false;
            }

            if (!password.value.trim()) {
                password.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>

</body>

</html>