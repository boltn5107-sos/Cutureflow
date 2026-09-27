# Couture Flow

Application de gestion pour ateliers de couture : clients, commandes, mesures,
paiements Wave, rendez-vous, modèles et dépenses.

- **Framework** : Laravel 12, PHP 8.2+
- **Base de données** : MySQL en développement local, PostgreSQL en production
- **Front** : Tailwind CSS 4, Vite 7

## Documentation

Le guide de mise en ligne et d'exploitation se trouve dans
**[GUIDE-DEPLOIEMENT.html](GUIDE-DEPLOIEMENT.html)** — ouvrez-le directement dans
votre navigateur.

## Installation locale

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

## Tests

```bash
php artisan test
vendor/bin/pint --test
```

## Commandes utiles

| Commande | Rôle |
| --- | --- |
| `php artisan coutureflow:admin email --nom="Nom"` | Crée le compte administrateur (production) |
| `php artisan coutureflow:notifier --days=7` | Envoie les rappels d'échéance |
