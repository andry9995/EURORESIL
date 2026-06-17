# EURORESIL — Symfony 7.1 / PHP 8.2 / MariaDB

Plateforme SaaS de résiliation de contrats d'assurance et d'envoi de LRE qualifiée (Universign + Letreco).

---

## Installation rapide

### 1. Prérequis

- PHP 8.2+ avec extensions : pdo_mysql, json, ctype, iconv, intl
- Composer 2.x
- MariaDB 10.11+
- Symfony CLI (recommandé)

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
# Éditer .env avec vos paramètres (BDD, clés API...)
```

### 4. Créer la base de données et exécuter la migration

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Lancer le serveur de développement

```bash
symfony serve
# ou
php -S localhost:8000 -t public/
```

---

## Structure du projet

```
euroresil/
├── composer.json                    # Dépendances Symfony 7.1
├── .env.example                     # Variables d'environnement
├── config/packages/
│   ├── doctrine.yaml                # ORM MariaDB
│   ├── security.yaml                # Authentification form_login / argon2id
│   ├── twig.yaml
│   └── framework.yaml
├── migrations/
│   └── Version20260601000000.php    # Schéma complet (7 tables + 19 assureurs)
├── public/
│   ├── index.php
│   ├── css/euroresil.css            # Design EURORESIL (extrait de la maquette)
│   └── js/euroresil.js              # Interactions wizard, toast, dashboard
├── src/
│   ├── Kernel.php
│   ├── Controller/
│   │   ├── SecurityController.php   # Login / logout / home
│   │   ├── RegistrationController.php # Inscription particulier + pro
│   │   ├── WizardController.php     # Wizard résiliation 4 étapes
│   │   ├── LrarController.php       # LRAR générique multi-métiers
│   │   ├── DashboardController.php  # Tableau de bord pro
│   │   ├── PackController.php       # Packs + facturation
│   │   └── WebhookController.php    # Webhooks Universign + Letreco
│   ├── Entity/
│   │   ├── User.php                 # Utilisateur (particulier + pro multi-métiers)
│   │   ├── Resiliation.php          # Demande de résiliation (statuts + JSONB)
│   │   ├── ResilPreuve.php          # Preuves Letreco (dépôt/réception/retrait/négligence)
│   │   ├── Pack.php                 # Pack de crédits LRE
│   │   ├── Invoice.php              # Facture
│   │   └── RefAssureur.php          # Référentiel assureurs (autocomplete)
│   ├── Form/
│   │   ├── RegistrationParticulierType.php
│   │   └── RegistrationProType.php
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── ResilationRepository.php
│   │   └── InvoiceRepository.php
│   └── Service/
│       ├── UniversignService.php    # Intégration API Universign (signature eIDAS)
│       ├── LetrecoService.php       # Intégration API Letreco (LRE qualifiée)
│       └── CreditService.php        # Gestion packs + tarification dégressive
└── templates/
    ├── base.html.twig               # Layout principal (nav + flashes)
    ├── base_public.html.twig        # Layout pages publiques (sans nav)
    ├── security/login.html.twig
    ├── registration/
    │   ├── choice.html.twig         # Choix Particulier / Pro
    │   ├── particulier.html.twig
    │   ├── pro.html.twig            # Formulaire adaptatif par métier
    │   ├── verify_email.html.twig   # Code 6 chiffres
    │   ├── forgot_password.html.twig
    │   └── reset_password.html.twig
    ├── wizard/
    │   ├── souscripteur.html.twig   # Étape 1
    │   ├── assureur.html.twig       # Étape 2 — autocomplete AJAX
    │   ├── contrat_type.html.twig   # Étape 3a — filtre interactif
    │   ├── contrat_detail.html.twig # Étape 3b — motifs légaux
    │   ├── recap_envoi.html.twig    # Étape 4 — signature + envoi
    │   └── success.html.twig        # Confirmation + timeline
    ├── dashboard/
    │   ├── index.html.twig          # Tableau de bord pro
    │   └── detail.html.twig         # Détail résiliation + preuves
    ├── packs/index.html.twig        # Packs dégressifs + historique
    ├── lrar/
    │   ├── compose.html.twig        # Composition LRAR générique
    │   └── success.html.twig
    └── emails/
        └── verification_code.html.twig
```

---

## Routes principales

| Route | URL | Description |
|-------|-----|-------------|
| app_login | GET/POST /auth/login | Connexion |
| app_register_choice | GET /auth/register | Choix profil |
| app_register_particulier | GET/POST /auth/register/particulier | Inscription particulier |
| app_register_pro | GET/POST /auth/register/pro | Inscription pro |
| app_verify_email | GET/POST /auth/verify-email | Code 6 chiffres |
| app_home | GET / | Redirect selon profil |
| app_wizard_souscripteur | GET/POST /wizard/souscripteur/{id?} | Étape 1 |
| app_wizard_assureur | GET/POST /wizard/assureur/{id} | Étape 2 |
| app_wizard_api_assureurs | GET /wizard/api/assureurs?q= | AJAX autocomplete |
| app_wizard_contrat_type | GET/POST /wizard/contrat-type/{id} | Étape 3a |
| app_wizard_contrat_detail | GET/POST /wizard/contrat-detail/{id} | Étape 3b |
| app_wizard_recap | GET/POST /wizard/recap/{id} | Étape 4 |
| app_wizard_sign | POST /wizard/sign/{id} | Déclenchement Universign |
| app_wizard_send | POST /wizard/send/{id} | Envoi Letreco |
| app_wizard_success | GET /wizard/success/{id} | Confirmation |
| app_dashboard | GET /dashboard | Tableau de bord |
| app_dashboard_detail | GET /dashboard/detail/{id} | Détail + preuves |
| app_packs | GET /packs | Packs + historique |
| app_packs_pricing | GET /packs/pricing?qty= | API tarification |
| app_packs_purchase | POST /packs/purchase | Achat |
| app_lrar_compose | GET /lrar/compose/{useCase} | Composition LRAR |
| app_lrar_send | POST /lrar/send | Envoi LRAR |
| webhook_universign | POST /webhook/universign | Webhook Universign |
| webhook_letreco | POST /webhook/letreco | Webhook Letreco |

---

## À implémenter en production

- [ ] **PdfGeneratorService** — Génération PDF lettre + mandat (Dompdf ou Puppeteer via process)
- [ ] **Stockage S3** — Uploader les PDFs signés + preuves (AWS S3 / OVH Object Storage)
- [ ] **Stripe** — Intégration paiement packs (webhook stripe → CreditService::purchasePack)
- [ ] **ORIAS** — Validation N° ORIAS à l'inscription courtiers (api.orias.fr)
- [ ] **Rate limiting** — symfony/rate-limiter sur les endpoints d'auth (10 req/min/IP)
- [ ] **Mailer production** — Configurer Brevo/Postmark dans .env MAILER_DSN
- [ ] **HTTPS + HSTS** — Configurer via reverse proxy (Nginx/Caddy)
- [ ] **Cron archivage** — Nettoyage codes de vérification expirés (symfony/scheduler)

---

## Intégrations partenaires

### Universign (signature eIDAS)
- Docs : https://developers.universign.com
- Service : `src/Service/UniversignService.php`
- Webhook : `POST /webhook/universign` (HMAC-SHA256)
- Configurer `UNIVERSIGN_API_KEY` et `UNIVERSIGN_WEBHOOK_SECRET` dans `.env`

### Letreco (LRE qualifiée)
- Service : `src/Service/LetrecoService.php`
- Webhook : `POST /webhook/letreco` (HMAC-SHA256)
- Configurer `LETRECO_API_KEY` et `LETRECO_WEBHOOK_SECRET` dans `.env`

---

## Tarification des packs

| Quantité | Prix unitaire HT | Palier |
|----------|------------------|--------|
| 1 – 24 | 4,90 € | Standard |
| 25 – 99 | 3,90 € | Avantage |
| 100 – 299 | 2,90 € | Pro |
| 300 – 599 | 2,40 € | Volume |
| 600+ | 1,95 € | Grand compte |

TVA 20% applicable sur tous les montants.
