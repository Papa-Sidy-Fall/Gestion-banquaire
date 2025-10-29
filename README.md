# API Banque - Système de Gestion de Comptes

Une API REST complète pour la gestion de comptes bancaires avec archivage automatique, développée avec Laravel 11.

## 🚀 Fonctionnalités

### ✅ Gestion des Comptes
- **Création de comptes** avec validation des données client
- **Lister les comptes** avec filtres et pagination
- **Mise à jour des informations** client et compte
- **Blocage de comptes** avec durée déterminée
- **Archivage automatique** des comptes bloqués expirés

### ✅ Sécurité et Authentification
- **Laravel Passport** pour l'authentification OAuth2
- **Rate limiting** pour protection contre les abus
- **Middleware de logging** pour traçabilité
- **Validation stricte** des données

### ✅ Automatisation
- **Jobs programmés** pour archivage quotidien
- **Déblocage automatique** des comptes expirés
- **Notifications** par email et SMS
- **Health checks** pour monitoring

### ✅ Documentation
- **Swagger/OpenAPI** documentation complète
- **Tests automatisés** des endpoints
- **Logs détaillés** pour debugging

## 🐳 Déploiement Docker

### Prérequis
- Docker & Docker Compose
- Git

### Installation en Développement

```bash
# Cloner le repository
git clone <repository-url>
cd api-banque

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Démarrer les services
docker-compose up -d

# Accéder à l'application
# API: http://localhost:8000
# Swagger: http://localhost:8000/api/documentation
```

### Variables d'Environnement

```env
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=your-password

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

## 🚀 Déploiement sur Render

### 1. Configuration Render
```yaml
# render.yaml
services:
  - type: web
    name: laravel-api
    runtime: docker
    buildCommand: "docker build -t laravel-api ."
    startCommand: "docker run -p $PORT:80 laravel-api"
    healthCheckPath: /health
```

### 2. Variables d'Environnement sur Render
- `APP_ENV=production`
- `APP_KEY` (généré automatiquement)
- `DATABASE_URL` (fourni par Render PostgreSQL)
- `APP_URL` (URL de votre service Render)

### 3. Déploiement
```bash
# Pousser sur votre repository
git add .
git commit -m "Ready for production"
git push origin main

# Render détectera automatiquement render.yaml et déploiera
```

## 📚 API Endpoints

### Comptes
- `GET /api/v1/comptes` - Lister les comptes (avec filtres)
- `POST /api/v1/comptes` - Créer un compte
- `GET /api/v1/comptes/{id}` - Afficher un compte
- `PATCH /api/v1/comptes/{id}` - Mettre à jour un compte
- `DELETE /api/v1/comptes/{id}` - Supprimer un compte
- `POST /api/v1/comptes/{id}/bloquer` - Bloquer un compte

### Authentification
- `POST /oauth/token` - Obtenir un token d'accès

### Documentation
- `GET /api/documentation` - Interface Swagger

### Monitoring
- `GET /health` - Health check

## 🧪 Tests

```bash
# Exécuter les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 📊 Monitoring

### Health Check
L'endpoint `/health` retourne l'état des services :
```json
{
  "status": "healthy",
  "timestamp": "2025-10-29T15:56:00Z",
  "services": {
    "database": {"status": "healthy"},
    "cache": {"status": "healthy"},
    "storage": {"status": "healthy"}
  }
}
```

### Logs
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Docker
docker-compose logs -f app
```

## 🔧 Maintenance

### Jobs Programmés
```bash
# Exécuter manuellement les jobs
php artisan archive:expired-blocked-accounts
php artisan unblock:expired-accounts

# Lister les jobs programmés
php artisan schedule:list
```

### Optimisation
```bash
# Cache de configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 🏗️ Architecture

```
├── app/
│   ├── Console/Commands/     # Commandes Artisan
│   ├── Events/              # Événements
│   ├── Exceptions/          # Exceptions personnalisées
│   ├── Http/
│   │   ├── Controllers/     # Contrôleurs API
│   │   ├── Middleware/      # Middlewares personnalisés
│   │   ├── Requests/        # Form requests
│   │   └── Resources/       # API Resources
│   ├── Jobs/                # Jobs en file d'attente
│   ├── Listeners/           # Écouteurs d'événements
│   ├── Models/              # Modèles Eloquent
│   ├── Observers/           # Observers de modèles
│   ├── Rules/               # Règles de validation
│   └── Traits/              # Traits réutilisables
├── database/
│   ├── factories/           # Factories pour tests
│   ├── migrations/          # Migrations base de données
│   └── seeders/             # Seeders
├── docker/                  # Configuration Docker
├── routes/
│   ├── api.php             # Routes API
│   └── web.php             # Routes web
└── tests/                  # Tests automatisés
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement

---

**Développé avec ❤️ par l'équipe API Banque**
