#!/bin/sh
# ==========================================================================
#  Point d'entrée du conteneur Couture Flow sur Render
# ==========================================================================
#  Render n'exécute qu'un seul processus et fournit le port d'écoute dans la
#  variable PORT. Ce script prépare le conteneur puis lance Nginx et PHP-FPM
#  sous supervisord.
#
#  Variables reconnues :
#    PORT                      port d'écoute imposé par Render (10000 par défaut)
#    PERSISTENT_STORAGE_PATH   racine du disque persistant (ex. /var/data) ;
#                              storage/app/private y est relié en symlink
#    AUTO_MIGRATE              "true" pour jouer les migrations au démarrage
#    DB_WAIT_ATTEMPTS          nombre de tentatives d'attente de la base
# ==========================================================================

set -eu

log() {
    printf '[entrypoint] %s\n' "$*"
}

fail() {
    printf '[entrypoint] ERREUR : %s\n' "$*" >&2
    exit 1
}

# Valeurs par défaut prudentes : jamais de trace d'erreur en production.
: "${APP_ENV:=production}"
: "${APP_DEBUG:=false}"
: "${LOG_LEVEL:=info}"
: "${PORT:=10000}"
: "${AUTO_MIGRATE:=true}"
: "${DB_WAIT_ATTEMPTS:=30}"
export APP_ENV APP_DEBUG LOG_LEVEL

APP_DIR=/var/www/html
cd "$APP_DIR"

# --------------------------------------------------------------------------
# 1. Nginx doit écouter sur le port fourni par Render
#
#    Le paquet Nginx d'Alpine inclut /etc/nginx/http.d/*.conf alors que
#    l'image officielle nginx utilise /etc/nginx/conf.d/*.conf. On détecte
#    le répertoire réellement inclus avant d'écrire le site.
# --------------------------------------------------------------------------
log "configuration de Nginx sur le port ${PORT}"

if grep -q 'include /etc/nginx/http.d/\*\.conf' /etc/nginx/nginx.conf; then
    NGINX_SITE=/etc/nginx/http.d/default.conf
else
    NGINX_SITE=/etc/nginx/conf.d/default.conf
fi

# Le vhost par défaut écoute sur le port 80 : il rendrait la page
# injoignable depuis Render et ferait échouer la vérification de santé.
mkdir -p "$(dirname "$NGINX_SITE")"
rm -f /etc/nginx/http.d/default.conf /etc/nginx/conf.d/default.conf

sed "s/__PORT__/${PORT}/g" \
    /etc/nginx/templates/default.conf.template \
    > "$NGINX_SITE"

# Une erreur de configuration ne doit pas se manifester par des 502
# obscurs : on arrête le conteneur tout de suite.
nginx -t || fail "configuration Nginx invalide (${NGINX_SITE})"

# --------------------------------------------------------------------------
# 2. Stockage persistant (preuves de paiement, photos d'atelier)
#
#    Sans cette étape, les fichiers téléversés disparaîtraient à chaque
#    redéploiement : le système de fichiers du conteneur est éphémère.
# --------------------------------------------------------------------------
if [ -n "${PERSISTENT_STORAGE_PATH:-}" ]; then
    log "montage du stockage persistant sur ${PERSISTENT_STORAGE_PATH}"

    mkdir -p "${PERSISTENT_STORAGE_PATH}/private" "${PERSISTENT_STORAGE_PATH}/public"

    if [ ! -w "${PERSISTENT_STORAGE_PATH}/private" ]; then
        fail "${PERSISTENT_STORAGE_PATH} n'est pas accessible en écriture : le disque persistant est-il monté ?"
    fi

    rm -rf "${APP_DIR}/storage/app/private"
    ln -sfn "${PERSISTENT_STORAGE_PATH}/private" "${APP_DIR}/storage/app/private"

    log "preuves et photos conservées dans ${PERSISTENT_STORAGE_PATH}/private"
else
    log "aucun disque persistant : les fichiers téléversés seront perdus au redémarrage"
fi

# --------------------------------------------------------------------------
# 3. Permissions d'écriture
# --------------------------------------------------------------------------
log "vérification des permissions"

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Le lien public sert le disque « public ». L'application sert ses fichiers
# sensibles via ses contrôleurs, ce n'est donc pas bloquant en cas d'échec.
php artisan storage:link --force >/dev/null 2>&1 ||
    log "lien public/storage non créé (sans effet sur les proofs de paiement)"

# --------------------------------------------------------------------------
# 4. Attente de la base de données
# --------------------------------------------------------------------------
wait_for_db() {
    [ -n "${DB_HOST:-}" ] || return 0

    attempts=0

    while [ "$attempts" -lt "${DB_WAIT_ATTEMPTS}" ]; do
        if php -r '
            $driver = getenv("DB_CONNECTION") ?: "mysql";
            $host   = getenv("DB_HOST");
            $port   = getenv("DB_PORT") ?: "3306";
            $db     = getenv("DB_DATABASE") ?: "";
            $user   = getenv("DB_USERNAME") ?: "";
            $pass   = getenv("DB_PASSWORD");
            $pass   = false === $pass ? "" : $pass;

            $options = [];

            if ("pgsql" === $driver) {
                $dsn = "pgsql:host=".$host.";port=".$port.";dbname=".$db.";connect_timeout=5";
            } else {
                $dsn = "mysql:host=".$host.";port=".$port.";dbname=".$db;
                $options[PDO::ATTR_TIMEOUT] = 5;
            }

            try {
                new PDO($dsn, $user, $pass, $options);
                exit(0);
            } catch (Throwable $e) {
                exit(1);
            }
        ' 2>/dev/null; then
            return 0
        fi

        attempts=$((attempts + 1))
        log "base de données indisponible (${attempts}/${DB_WAIT_ATTEMPTS})"
        sleep 2
    done

    return 1
}

if ! wait_for_db; then
    log "la base reste injoignable : les migrations échoueront si AUTO_MIGRATE est actif"
fi

# --------------------------------------------------------------------------
# 5. Migrations
# --------------------------------------------------------------------------
if [ "${AUTO_MIGRATE}" = "true" ]; then
    log "application des migrations"

    attempt=0
    max=3

    until php artisan migrate --force --no-interaction; do
        attempt=$((attempt + 1))

        if [ "$attempt" -ge "$max" ]; then
            fail "les migrations ont échoué après ${max} tentatives (le déploiement est annulé)"
        fi

        log "échec de la migration, nouvelle tentative dans 5 s (${attempt}/${max})"
        sleep 5
    done
else
    log "AUTO_MIGRATE désactivée : migrations à lancer manuellement"
fi

# --------------------------------------------------------------------------
# 6. Caches — ils lisent les variables d'environnement fournies par Render,
#    ils ne doivent donc pas être produits pendant la construction de l'image.
# --------------------------------------------------------------------------
log "génération des caches"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || log "cache d'événements ignoré"

# --------------------------------------------------------------------------
# 7. Démarrage
# --------------------------------------------------------------------------
log "démarrage de Nginx et PHP-FPM"

exec supervisord -c /etc/supervisord.conf
