# Script d'installation rapide de Stripe pour Malisafi MLS
# Exécutez ce script dans PowerShell

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Malisafi MLS - Installation Stripe" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

$pluginPath = "c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls"

# Vérifier si le dossier du plugin existe
if (-Not (Test-Path $pluginPath)) {
    Write-Host "❌ Erreur: Le dossier du plugin n'existe pas: $pluginPath" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Dossier du plugin trouvé" -ForegroundColor Green
Write-Host ""

# Vérifier si Composer est installé
Write-Host "Vérification de Composer..." -ForegroundColor Yellow
$composerInstalled = Get-Command composer -ErrorAction SilentlyContinue

if ($composerInstalled) {
    Write-Host "✓ Composer est installé" -ForegroundColor Green
    Write-Host ""
    
    # Option 1: Installation avec Composer
    Write-Host "Installation de la bibliothèque Stripe PHP avec Composer..." -ForegroundColor Yellow
    Set-Location $pluginPath
    
    # Créer composer.json si nécessaire
    if (-Not (Test-Path "$pluginPath\composer.json")) {
        Write-Host "Création du fichier composer.json..." -ForegroundColor Yellow
        $composerJson = @"
{
    "name": "malisafi/mls-plugin",
    "description": "Malisafi MLS WordPress Plugin",
    "require": {
        "stripe/stripe-php": "^10.0"
    }
}
"@
        Set-Content -Path "$pluginPath\composer.json" -Value $composerJson
        Write-Host "✓ composer.json créé" -ForegroundColor Green
    }
    
    # Installer Stripe
    Write-Host "Exécution de 'composer install'..." -ForegroundColor Yellow
    composer install
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Bibliothèque Stripe installée avec succès!" -ForegroundColor Green
    } else {
        Write-Host "❌ Erreur lors de l'installation de Stripe" -ForegroundColor Red
        exit 1
    }
    
} else {
    Write-Host "⚠ Composer n'est pas installé" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Option 1: Installer Composer" -ForegroundColor Cyan
    Write-Host "  Téléchargez depuis: https://getcomposer.org/download/" -ForegroundColor White
    Write-Host ""
    Write-Host "Option 2: Installation manuelle" -ForegroundColor Cyan
    Write-Host "  1. Téléchargez Stripe PHP: https://github.com/stripe/stripe-php/releases" -ForegroundColor White
    Write-Host "  2. Extrayez dans: $pluginPath\vendor\stripe\stripe-php\" -ForegroundColor White
    Write-Host ""
    
    $response = Read-Host "Voulez-vous télécharger et installer manuellement maintenant? (o/n)"
    
    if ($response -eq "o" -or $response -eq "O") {
        Write-Host ""
        Write-Host "Installation manuelle..." -ForegroundColor Yellow
        
        # Créer le dossier vendor
        $vendorPath = "$pluginPath\vendor\stripe"
        if (-Not (Test-Path $vendorPath)) {
            New-Item -ItemType Directory -Path $vendorPath -Force | Out-Null
            Write-Host "✓ Dossier vendor créé" -ForegroundColor Green
        }
        
        # Télécharger Stripe PHP
        $stripeUrl = "https://github.com/stripe/stripe-php/archive/refs/heads/master.zip"
        $zipPath = "$env:TEMP\stripe-php.zip"
        
        Write-Host "Téléchargement de Stripe PHP..." -ForegroundColor Yellow
        try {
            Invoke-WebRequest -Uri $stripeUrl -OutFile $zipPath
            Write-Host "✓ Téléchargement terminé" -ForegroundColor Green
            
            # Extraire
            Write-Host "Extraction..." -ForegroundColor Yellow
            Expand-Archive -Path $zipPath -DestinationPath "$env:TEMP\stripe-temp" -Force
            
            # Déplacer dans le bon dossier
            $extractedPath = "$env:TEMP\stripe-temp\stripe-php-master"
            if (Test-Path $extractedPath) {
                Move-Item -Path $extractedPath -Destination "$vendorPath\stripe-php" -Force
                Write-Host "✓ Installation terminée" -ForegroundColor Green
            }
            
            # Nettoyer
            Remove-Item $zipPath -Force
            Remove-Item "$env:TEMP\stripe-temp" -Recurse -Force
            
        } catch {
            Write-Host "❌ Erreur lors du téléchargement: $_" -ForegroundColor Red
            Write-Host "Veuillez installer manuellement." -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Vérification de l'installation" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que le fichier init.php existe
$initPath = "$pluginPath\vendor\stripe\stripe-php\init.php"
if (Test-Path $initPath) {
    Write-Host "✓ Stripe PHP est correctement installé!" -ForegroundColor Green
    Write-Host "  Chemin: $initPath" -ForegroundColor Gray
} else {
    Write-Host "❌ Stripe PHP n'est pas installé" -ForegroundColor Red
    Write-Host "  Attendu: $initPath" -ForegroundColor Gray
}

Write-Host ""
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Prochaines étapes" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Créez un compte Stripe sur https://dashboard.stripe.com" -ForegroundColor White
Write-Host "2. Récupérez vos clés API de test" -ForegroundColor White
Write-Host "3. Allez dans WordPress Admin > Malisafi > Subscriptions" -ForegroundColor White
Write-Host "4. Configurez vos clés Stripe" -ForegroundColor White
Write-Host "5. Créez vos produits dans Stripe Dashboard" -ForegroundColor White
Write-Host "6. Configurez le webhook" -ForegroundColor White
Write-Host ""
Write-Host "📖 Guide complet: STRIPE_SETUP_GUIDE.md" -ForegroundColor Cyan
Write-Host ""

Write-Host "Appuyez sur une touche pour continuer..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
