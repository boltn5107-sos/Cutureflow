#!/bin/sh
# ==========================================================================
#  Point d'entrée du conteneur Couture+ sur Render
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
#
#  Base de données : Render fournit sa PostgreSQL via DB_URL (réseau privé,
#  même région). DB_HOST/DB_PORT sont également acceptés pour une base
#  externe. Aucun réglage SSL n'est nécessaire dans les deux cas.
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
# 0. Clé d'application
#
#    Le chiffrement utilise AES-256-CBC, qui exige une clé de 32 octets
#    exactement. Laravel ne décode la valeur que si elle commence par
#    « base64: » : une base64 collée sans ce préfixe est prise telle quelle
#    et fait échouer chaque requête avec un « Unsupported cipher » que rien
#    n'explique. Le contrôle ci-dessous transforme ce message opaque en une
#    phrase lisible dans les journaux du déploiement.
# --------------------------------------------------------------------------
log "vérification de la clé d'application"

APP_KEY_ERREUR=$(php -r '
    $cle = getenv("APP_KEY") ?: "";

    if ($cle === "") {
        echo "la variable APP_KEY est absente du service";
        exit(1);
    }

    // Laravel ne décode la base64 que si le préfixe est présent.
    $brut = str_starts_with($cle, "base64:")
        ? base64_decode(substr($cle, 7), true)
        : $cle;

    if ($brut === false) {
        echo "la valeur qui suit le préfixe base64: est illisible";
        exit(1);
    }

    $taille = strlen($brut);

    if ($taille !== 32) {
        echo "il faut exactement 32 octets, la valeur en contient ".$taille;

        if ($taille === 44) {
            echo " : le préfixe base64: a probablement été oublié";
        }

        exit(1);
    }
' 2>&1) || fail "clé d'application invalide : ${APP_KEY_ERREUR}"

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

    # chown -R ne descend pas dans un lien symbolique : la cible est donc
    # traitée explicitement, sinon PHP-FPM (www-data) ne pourrait pas y écrire.
    chown -R www-data:www-data "${PERSISTENT_STORAGE_PATH}/private" 2>/dev/null ||
        log "droits de ${PERSISTENT_STORAGE_PATH}/private inchangés (déjà propriétés de www-data)"

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
    [ -n "${DB_URL:-}${DB_HOST:-}" ] || return 0

    attempts=0

    while [ "$attempts" -lt "${DB_WAIT_ATTEMPTS}" ]; do
        if php -r '
            $driver = getenv("DB_CONNECTION") ?: "mysql";

            // Render fournit la base PostgreSQL via une URL unique
            // (fromDatabase: connectionString) : il faut la decomposer.
            $url = getenv("DB_URL") ?: "";

            if ($url) {
                $parts = parse_url($url);
                $host  = $parts["host"] ?? "";
                $port  = $parts["port"] ?? "";
                $db    = ltrim($parts["path"] ?? "", "/");
                $user  = isset($parts["user"]) ? rawurldecode($parts["user"]) : "";
                $pass  = isset($parts["pass"]) ? rawurldecode($parts["pass"]) : "";
            } else {
                $host  = getenv("DB_HOST") ?: "";
                $port  = getenv("DB_PORT") ?: "";
                $db    = getenv("DB_DATABASE") ?: "";
                $user  = getenv("DB_USERNAME") ?: "";
                $pass  = getenv("DB_PASSWORD");
                $pass  = false === $pass ? "" : $pass;
            }

            if (! $host) {
                exit(0);
            }

            $options = [];

            if ("pgsql" === $driver) {
                $port = $port ?: "5432";
                $dsn = "pgsql:host=".$host.";port=".$port.";dbname=".$db.";connect_timeout=5";
            } else {
                $port = $port ?: "3306";
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
#
#    La base PostgreSQL de Render est jointe par le réseau privé de Render
#    (même région) : aucun certificat ni réglage SSL n'est à installer.
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
