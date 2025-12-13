<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Formulaire d'Inscription Malisafi</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .test-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .test-info {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .test-info h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .test-card h3 {
            margin-top: 0;
            color: #667eea;
        }
        .test-card code {
            background: #f1f3f5;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        .shortcode-demo {
            background: #667eea;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .shortcode-demo code {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 18px;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1>🧪 Page de Test - Système d'Inscription Malisafi</h1>
        <p>Cette page vous permet de tester le nouveau formulaire d'inscription conversationnel</p>
    </div>

    <div class="test-info">
        <h2>📋 Instructions de Test</h2>
        
        <div class="test-grid">
            <div class="test-card">
                <h3>🏠 Test Client</h3>
                <ul>
                    <li>Sélectionner "Find a Property"</li>
                    <li>Remplir les 3 étapes</li>
                    <li>Email: <code>client@test.com</code></li>
                    <li>Username: <code>testclient</code></li>
                </ul>
            </div>

            <div class="test-card">
                <h3>💼 Test Agent</h3>
                <ul>
                    <li>Sélectionner "Work as an Agent"</li>
                    <li>Remplir avec champs agence</li>
                    <li>Email: <code>agent@test.com</code></li>
                    <li>Username: <code>testagent</code></li>
                </ul>
            </div>

            <div class="test-card">
                <h3>🔑 Test Propriétaire</h3>
                <ul>
                    <li>Sélectionner "List My Property"</li>
                    <li>Remplir les informations</li>
                    <li>Email: <code>owner@test.com</code></li>
                    <li>Username: <code>testowner</code></li>
                </ul>
            </div>

            <div class="test-card">
                <h3>🏗️ Test Développeur</h3>
                <ul>
                    <li>Sélectionner "I'm a Developer"</li>
                    <li>Compléter le formulaire</li>
                    <li>Email: <code>dev@test.com</code></li>
                    <li>Username: <code>testdev</code></li>
                </ul>
            </div>
        </div>

        <div class="shortcode-demo">
            <h2>💻 Shortcode Utilisé</h2>
            <code>[malisafi_registration]</code>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">
                Copiez ce shortcode dans n'importe quelle page WordPress
            </p>
        </div>

        <h2>✅ Points à Vérifier</h2>
        <div class="test-grid">
            <div class="test-card">
                <h3>Navigation</h3>
                <ul>
                    <li>✓ Barre de progression</li>
                    <li>✓ Boutons Suivant/Retour</li>
                    <li>✓ Transitions fluides</li>
                    <li>✓ Désactivation si invalide</li>
                </ul>
            </div>

            <div class="test-card">
                <h3>Validation</h3>
                <ul>
                    <li>✓ Email en temps réel</li>
                    <li>✓ Username disponible</li>
                    <li>✓ Force du mot de passe</li>
                    <li>✓ Messages d'erreur clairs</li>
                </ul>
            </div>

            <div class="test-card">
                <h3>Design</h3>
                <ul>
                    <li>✓ Responsive mobile</li>
                    <li>✓ Animations</li>
                    <li>✓ Icônes/Emojis</li>
                    <li>✓ Couleurs cohérentes</li>
                </ul>
            </div>

            <div class="test-card">
                <h3>Fonctionnement</h3>
                <ul>
                    <li>✓ Création de compte</li>
                    <li>✓ Email de bienvenue</li>
                    <li>✓ Connexion auto</li>
                    <li>✓ Redirection correcte</li>
                </ul>
            </div>
        </div>

        <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <h3 style="margin-top: 0; color: #856404;">⚠️ Note Importante</h3>
            <p style="margin: 0; color: #856404;">
                <strong>Environnement de Test :</strong> Assurez-vous d'être sur un environnement de développement/staging.
                Ne testez pas sur la production avec de fausses données !
            </p>
        </div>

        <div style="background: #d1ecf1; border: 2px solid #17a2b8; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <h3 style="margin-top: 0; color: #0c5460;">💡 Console de Debug</h3>
            <p style="color: #0c5460; margin: 0;">
                Ouvrez la console du navigateur (F12) pour voir les logs JavaScript et détecter d'éventuelles erreurs.
                Tapez <code style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 3px;">malisafiRegistration</code> 
                pour voir la configuration AJAX.
            </p>
        </div>
    </div>

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e9ecef;">

    <!-- LE FORMULAIRE S'AFFICHE ICI -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px 40px;">
        <?php 
        // Si WordPress est chargé, affiche le shortcode
        if (function_exists('do_shortcode')) {
            echo do_shortcode('[malisafi_registration]'); 
        } else {
            echo '<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 30px; border-radius: 8px; text-align: center;">
                    <h2 style="color: #721c24;">❌ WordPress Non Chargé</h2>
                    <p style="color: #721c24;">Cette page doit être intégrée dans WordPress pour afficher le formulaire.</p>
                    <p style="color: #721c24;"><strong>Solution :</strong> Créez une page WordPress et utilisez le shortcode <code>[malisafi_registration]</code></p>
                  </div>';
        }
        ?>
    </div>

    <div style="background: #2c3e50; color: white; padding: 30px; text-align: center;">
        <h3>📚 Documentation Disponible</h3>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
            <a href="REGISTRATION-QUICK-START.md" style="color: #667eea; text-decoration: none; font-weight: bold;">
                🚀 Démarrage Rapide
            </a>
            <a href="REGISTRATION-SYSTEM-GUIDE.md" style="color: #667eea; text-decoration: none; font-weight: bold;">
                📖 Guide Complet
            </a>
            <a href="REGISTRATION-CHANGES.md" style="color: #667eea; text-decoration: none; font-weight: bold;">
                📝 Modifications
            </a>
        </div>
        <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">
            Développé pour Malisafi MLS - Décembre 2025
        </p>
    </div>

    <script>
        // Log de debug pour vérifier le chargement
        console.log('%c🚀 Page de Test Malisafi', 'color: #667eea; font-size: 20px; font-weight: bold;');
        console.log('Si vous voyez ce message, JavaScript fonctionne correctement.');
        
        // Vérifier si l'objet malisafiRegistration existe
        setTimeout(() => {
            if (typeof malisafiRegistration !== 'undefined') {
                console.log('%c✅ Configuration AJAX chargée', 'color: green; font-weight: bold;');
                console.log(malisafiRegistration);
            } else {
                console.warn('%c⚠️ Configuration AJAX non trouvée', 'color: orange; font-weight: bold;');
                console.log('Assurez-vous que le shortcode [malisafi_registration] est bien sur une page WordPress');
            }
        }, 1000);
    </script>
</body>
</html>
