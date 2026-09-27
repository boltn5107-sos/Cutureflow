# Déploiement sur Render

Guide de mise en production de Couture Flow sur [Render](https://render.com).
Tous les fichiers nécessaires sont déjà dans le dépôt : rien à écrire à la main
dans le tableau de bord, à part les secrets.

| Fichier | Rôle |
| --- | --- |
| `Dockerfile` | Image de production : Vite, Composer, PHP-FPM + Nginx |
| `.dockerignore` | Empêche `.env`, `vendor/` et les données locales d'entrer dans l'image |
| `docker/entrypoint.sh` | Port, disque persistant, migrations, caches, démarrage |
| `docker/nginx/default.conf.template` | Vhost, sécurité, cache des assets |
| `docker/php/zz-app.ini` | `upload_max_filesize`, OPcache, journaux sur stderr |
| `docker/php/www.conf` | Pool PHP-FPM (`clear_env = no`, indispensable) |
| `docker/supervisord.conf` | Nginx et PHP-FPM dans un seul conteneur |
| `render.yaml` | Blueprint : service web + disque + variables |

> **L'image n'a pas pu être construite sur la machine de développement**
> (Docker n'y est pas installé). Le premier déploiement sur Render sert donc
> de test : surveillez les logs, la correction d'un souci de build ne coûte
> qu'un redéploiement.

---

## 1. Préparer la base de données

Render ne propose **pas de MySQL managé**. Deux options :

**A. MySQL externe (recommandé, aucun code à toucher)**

Créer une base gratuite chez [Aiven](https://aiven.io), [Pi-lon](https://pi-lon.fr)
ou [PlanetScale](https://planetscale.com), puis relever hôte, port, nom de base,
utilisateur et mot de passe.

> Les migrations utilisent `->enum()`. C'est natif sur MySQL. Sur PostgreSQL,
> Laravel émule l'énumération par une colonne texte : le schéma passe, mais
> ce chemin n'a pas été testé sur ce projet.

**B. PostgreSQL inclus dans Render**

Créer le service PostgreSQL dans le même Blueprint ou via
*New > PostgreSQL*, puis sur le service web :

```
DB_CONNECTION=pgsql
DB_HOST=<host interne fourni par Render>
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

L'image compile `pdo_pgsql`, donc aucun changement de Dockerfile n'est requis.

---

## 2. Créer le service web

**Option A — Blueprint (le plus rapide)**

Dans Render : *Blueprints > New Blueprint Instance*, sélectionner le dépôt.
Render lit `render.yaml`, crée le service, le disque et les variables non
sensibles. `APP_KEY` est généré automatiquement.

**Option B — Manuelle**

*New > Web Service > Connecter le dépôt*. Render détecte le `Dockerfile`,
le `healthCheckPath` (`/up`) et le plan. Ajouter ensuite le disque persistant
(*Disks > Add Disk*, montage sur `/var/data`, 10 Go) et les variables à la main.

Plan `starter` minimum : le plan `free` s'endort après 15 minutes et
**ne supporte pas de disque persistant**.

---

## 3. Variables à saisir

Tout ce qui est marqué `sync: false` dans `render.yaml` doit être rempli dans
*Environment* :

| Variable | Exemple | Nécessaire |
| --- | --- | --- |
| `APP_URL` | `https://coutureflow.onrender.com` | oui |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | — | oui |
| `MAIL_FROM_ADDRESS` | `contact@votre-domaine.sn` | recommandé |
| `WAVE_PAYMENT_NUMBER` | `221 77 896 91 84` | oui |
| `WAVE_PAYMENT_NAME` | `COUTURE FLOW` | oui |

`APP_KEY` est généré par Render. **Ne le régénérez jamais** : le changer
invalide les cookies de session, les jetons de réinitialisation de mot de passe
et toutes les données chiffrées.

---

## 4. Créer le premier administrateur

Le site d'installation `/installation` est volontairement neutralisé sur Render :
il écrit un fichier `.env`, alors que sur Render les variables viennent de la
plateforme et que le conteneur est recréé à chaque déploiement.

Une fois le premier déploiement en ligne (base migrée automatiquement) :

```bash
php artisan coutureflow:admin admin@votre-domaine.sn --nom="Nom" --force
```

La commande affiche un mot de passe généré, affiché **une seule fois** :

```
Administrateur créé : admin@votre-domaine.sn (#1).
Mot de passe : k7Trq2mVx9Lp4dRb
```

Elle est idempotente : relancer la commande avec `--password` réinitialise le
compte sans le supprimer.

---

## 5. Après le déploiement

1. `https://coutureflow.onrender.com/up` doit répondre `200`.
2. Se connecter avec le compte admin et vérifier que le tableau de bord
   s'affiche (le disque `/var/data` est monté, donc les uploads fonctionneront).
3. Faire une inscription réelle avec une capture d'écran, puis vérifier que
   l'administrateur voit la preuve **en ligne**.
4. Brancher le domaine personnalisé (*Settings > Custom Domains*), renseigner
   l'URL dans `APP_URL`, puis redéployer.

---

## 6. Ce qu'il faut savoir

**Les fichiers téléversés survivent aux redéploiements** uniquement grâce au
disque persistant. Sans lui, les preuves Wave et les photos disparaîtraient à
chaque déploiement : le disque `/var/data` n'est pas une option.

**Les sessions, le cache et la file d'attente sont en base de données**, ce qui
est obligatoire ici : le système de fichiers du conteneur disparaît à chaque
redéploiement.

**Aucune file d'attente de jobs n'est nécessaire** : aucun modèle n'implémente
`ShouldQueue`, les notifications sont enregistrées en base et lues dans
l'interface. Le `CoutureFlowNotifieCommand` se lance manuellement :

```bash
php artisan coutureflow:notifier --days=7
```

Si vous voulez l'automatiser, Render n'expose les tâches cron que pour ses
runtimes natifs : il faudra passer par un service externe qui appelle une route
protégée, ou un second service.

**Les migrations sont jouées à chaque démarrage** (`AUTO_MIGRATE=true`). C'est
pratique mais dangereux au-delà d'une instance : à partir de deux instances,
mettez `AUTO_MIGRATE=false` et jouez les migrations une seule fois.

**Les notifications d'échéance arrivent seulement après la première exécution**
du notificateur : le fichier `routes/console.php` ne définit aucun planning.

---

## 7. Tester l'image en local

```bash
docker build -t coutureflow .
docker run --rm -p 8080:8080 -e PORT=8080 --env-file .env coutureflow
```

L'application répond alors sur `http://localhost:8080`. Gardez `PORT` : Nginx
écoute sur le port fourni par la variable, jamais sur 80.
