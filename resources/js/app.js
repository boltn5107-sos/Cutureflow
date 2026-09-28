import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('confirm', {
    open: false,
    title: 'Confirmer',
    message: 'Cette action est définitive.',
    confirmLabel: 'Confirmer',
    tone: 'danger',
    onConfirm: null,

    ask({ title, message, confirmLabel, cancelLabel, tone, onConfirm }) {
        this.title = title ?? 'Confirmer';
        this.message = message ?? 'Cette action est définitive.';
        this.confirmLabel = confirmLabel ?? 'Confirmer';
        this.cancelLabel = cancelLabel ?? 'Annuler';
        this.tone = tone ?? 'danger';
        this.onConfirm = onConfirm ?? null;
        this.open = true;
    },

    close() {
        this.open = false;
        this.onConfirm = null;
    },

    confirm() {
        const callback = this.onConfirm;
        this.close();

        if (typeof callback === 'function') {
            callback();
        }
    },
});

Alpine.data('themeToggle', () => ({
    /*
     * Rejoue exactement la décision du script <head>, sans jamais écrire dans
     * localStorage. Un simple « ?? 'light' » forçait le mode clair sur les
     * écrans réglés en mode sombre, et annulait la préférence du système dès
     * le premier rendu d'Alpine.
     */
    init() {
        const stored = localStorage.getItem('cf-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        this.apply(stored ?? (prefersDark ? 'dark' : 'light'));
    },
    toggle() {
        const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        localStorage.setItem('cf-theme', next);
        this.apply(next);
    },
    apply(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
    },
}));

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
|
| Les notifications sont enregistrées en base et rendues à l'ouverture de
| la page. Rien ne les poussait vers un navigateur déjà ouvert : la pastille
| et le son passaient donc inaperçus tant que l'on ne rechargait pas.
|
| Ce store comble ce manque par une interrogation périodique d'un point
| d'état JSON. Il ne s'appuie sur aucun websockets : l'application est
| déployée sans serveur de diffusion ni file de travail, et un sondage
| espacé tient dans ce budget.
|
| Le son est synthétisé par l'API Web Audio plutôt que stocké : aucun
| fichier binaire à héberger, à mettre en cache hors-ligne ni à recommencer
| à chaque version. Deux notes suffisent à être reconnu sans être agressif.
|
*/
Alpine.store('notifications', {
    url: null,
    dernierId: null,
    nonLues: 0,
    sonActif: true,
    sonPret: false,
    toasts: [],
    minuterie: null,
    contexte: null,
    delai: 30000,
    delaiCache: 180000,

    demarrer() {
        const racine = document.querySelector('[data-notifications]');

        // Pages publiques, installation : personne n'est connecté, rien à suivre.
        if (!racine) {
            return;
        }

        this.url = racine.dataset.notifications;
        this.dernierId = racine.dataset.dernierId || null;
        this.nonLues = Number(racine.dataset.nonLues || 0);
        this.sonActif = localStorage.getItem('cf-son') !== 'non';

        /*
         * Les navigateurs refusent de produire un son tant que l'utilisateur
         * n'a pas interacti avec la page. Le premier clic ou la première
         * touche sert donc de déclencheur, et le bouton de volume permet de
         * déverrouiller explicitement ensuite.
         */
        const deverrouiller = () => this.preparer();
        document.addEventListener('pointerdown', deverrouiller, { once: true });
        document.addEventListener('keydown', deverrouiller, { once: true });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.planifier();
                this.verifier();
            }
        });

        this.planifier();
    },

    /*
     * Chaîne de setTimeout plutôt qu'un setInterval : chaque réponse peut
     * ajuster l'intervalle suivant, et une page mise en arrière-plan est
     * sondée trois fois moins souvent.
     */
    planifier() {
        clearTimeout(this.minuterie);
        this.minuterie = setTimeout(() => {
            this.verifier().finally(() => this.planifier());
        }, document.hidden ? this.delaiCache : this.delai);
    },

    async verifier() {
        if (!this.url) {
            return;
        }

        try {
            const reponse = await fetch(this.url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!reponse.ok) {
                return;
            }

            const etat = await reponse.json();
            const dernier = etat.dernier;

            this.nonLues = Number(etat.nonLues || 0);

            if (!dernier) {
                this.dernierId = null;

                return;
            }

            if (dernier.id !== this.dernierId) {
                this.dernierId = dernier.id;
                this.annoncer(dernier);
            }
        } catch (e) {
            // Silencieux : hors-ligne ou onglet fermé, l'essai suivant suffira.
        }
    },

    annoncer(dernier) {
        this.jouer();
        this.toasts.push({ ...dernier, cle: Date.now() });

        // Au-delà de trois, l'écran est saturé et la pile ne sert plus à rien.
        while (this.toasts.length > 3) {
            this.toasts.shift();
        }

        setTimeout(() => this.fermer(dernier.id), 12000);
    },

    fermer(id) {
        this.toasts = this.toasts.filter((toast) => String(toast.id) !== String(id));
    },

    preparer() {
        if (this.sonPret) {
            return true;
        }

        const Contexte = window.AudioContext || window.webkitAudioContext;

        if (!Contexte) {
            this.sonActif = false;
            this.sonPret = false;

            return false;
        }

        this.contexte = this.contexte || new Contexte();

        if (this.contexte.state === 'suspended') {
            this.contexte.resume();
        }

        this.sonPret = this.contexte.state === 'running';

        return this.sonPret;
    },

    /*
     * Deux notes brèves, en quintale, avec une attack courte et une
     * décroissance exponentielle : un clochement plutôt qu'un bip.
     */
    jouer() {
        if (!this.sonActif || !this.preparer()) {
            return;
        }

        const contexte = this.contexte;
        const depart = contexte.currentTime;

        [880, 1320].forEach((frequence, index) => {
            const debut = depart + index * 0.16;
            const oscillateur = contexte.createOscillator();
            const volume = contexte.createGain();

            oscillateur.type = 'sine';
            oscillateur.frequency.value = frequence;

            volume.gain.setValueAtTime(0, debut);
            volume.gain.linearRampToValueAtTime(0.16, debut + 0.02);
            volume.gain.exponentialRampToValueAtTime(0.0001, debut + 0.5);

            oscillateur.connect(volume).connect(contexte.destination);
            oscillateur.start(debut);
            oscillateur.stop(debut + 0.55);
        });
    },

    /*
     * Activer le son joue un aperçu : le clic est un geste de l'utilisateur,
     * c'est donc aussi ce qui déverrouille l'API Web Audio.
     */
    basculerSon() {
        this.sonActif = !this.sonActif;
        localStorage.setItem('cf-son', this.sonActif ? 'oui' : 'non');

        if (this.sonActif) {
            this.jouer();
        }
    },
});

/*
|--------------------------------------------------------------------------
| Partage
|--------------------------------------------------------------------------
|
| Les liens de partage sont construits ici plutôt que dans les vues : un
| seul endroit tient la liste des canaux, et le composant Blade se contente
| de passer le titre, le texte, l'adresse et le numéro du destinataire.
|
| L'API de partage du système d'exploitation est prioritaire quand elle
| existe (feuille native sur mobile, apps déjà installées). Sinon, la
| feuille de canaux prend le relais, avec un repli de copie qui fonctionne
| aussi en HTTP simple, où l'API presse-papiers est indisponible.
|
*/
Alpine.data('partage', (options = {}) => ({
    titre: options.titre ?? '',
    texte: options.texte ?? '',
    /*
     * « || » et non « ?? » : la vue transmet une chaîne vide quand elle
     * n'a rien à donner, et l'URL courante est alors le bon repli. Avec
     * « ?? », la chaîne vide passerait et produirait un lien vide.
     */
    url: options.url || window.location.href,
    telephone: options.telephone ?? '',
    ouvert: false,
    copie: false,

    get natif() {
        return typeof navigator.share === 'function';
    },

    get lienWhatsapp() {
        const message = encodeURIComponent(`${this.texte} ${this.url}`.trim());

        return this.telephone
            ? `https://wa.me/${this.telephone}?text=${message}`
            : `https://wa.me/?text=${message}`;
    },

    get lienFacebook() {
        return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(this.url)}`;
    },

    get lienTelegram() {
        return `https://t.me/share/url?url=${encodeURIComponent(this.url)}&text=${encodeURIComponent(this.texte)}`;
    },

    get lienX() {
        return `https://twitter.com/intent/tweet?text=${encodeURIComponent(this.texte)}&url=${encodeURIComponent(this.url)}`;
    },

    get lienEmail() {
        return `mailto:?subject=${encodeURIComponent(this.titre)}&body=${encodeURIComponent(`${this.texte}\n${this.url}`.trim())}`;
    },

    async partager() {
        if (this.natif) {
            try {
                await navigator.share({ title: this.titre, text: this.texte, url: this.url });

                return;
            } catch (e) {
                // Partage annulé par l'utilisateur : rien à rattraper.
                if (e && e.name === 'AbortError') {
                    return;
                }
            }
        }

        this.ouvert = !this.ouvert;
    },

    async copier() {
        const texte = `${this.texte}\n${this.url}`.trim();

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(texte);
            } else {
                this.copierSecours(texte);
            }

            this.copie = true;
            setTimeout(() => {
                this.copie = false;
            }, 2000);
        } catch (e) {
            // Le presse-papiers peut être refusé : le lien reste affiché.
        }
    },

    /*
     * L'API presse-papiers exige un contexte sécurisé. En développement
     * sur http://localhost elle est absente, et seule la sélection manuelle
     * d'un champ permet encore de copier.
     */
    copierSecours(texte) {
        const champ = document.createElement('textarea');
        champ.value = texte;
        champ.setAttribute('readonly', '');
        champ.style.position = 'fixed';
        champ.style.opacity = '0';

        document.body.appendChild(champ);
        champ.select();

        try {
            document.execCommand('copy');
        } finally {
            champ.remove();
        }
    },
}));

Alpine.data('filePreview', () => ({
    fileName: '',
    fileSize: '',
    isPdf: false,
    error: '',
    handle(event) {
        this.error = '';
        const file = event.target.files?.[0];
        if (!file) {
            this.fileName = '';
            this.fileSize = '';
            this.isPdf = false;
            return;
        }
        const maxKb = Number(event.target.dataset.maxKb || 4096);
        if (file.size > maxKb * 1024) {
            this.error = `Fichier trop volumineux (max ${Math.round(maxKb / 1024)} Mo).`;
            event.target.value = '';
            this.fileName = '';
            this.fileSize = '';
            this.isPdf = false;
            return;
        }
        this.fileName = file.name;
        this.fileSize = file.size > 1024 * 1024
            ? `${(file.size / (1024 * 1024)).toFixed(2)} Mo`
            : `${Math.round(file.size / 1024)} Ko`;
        this.isPdf = file.type === 'application/pdf';
    },
}));

Alpine.data('multiPhotoPreview', () => ({
    previews: [],
    handle(event) {
        this.previews = [];
        for (const file of event.target.files ?? []) {
            this.previews.push({
                name: file.name,
                url: URL.createObjectURL(file),
            });
        }
    },
    remove(index) {
        URL.revokeObjectURL(this.previews[index].url);
        this.previews.splice(index, 1);
    },
}));

Alpine.data('soldeCalcul', (prixTotal, avance) => ({
    prixTotal: Number(prixTotal) || 0,
    avance: Number(avance) || 0,
    get solde() {
        return Math.max(this.prixTotal - this.avance, 0);
    },
    get progress() {
        if (this.prixTotal <= 0) return 0;
        return Math.min(Math.round((this.avance / this.prixTotal) * 100), 100);
    },
    format(value) {
        return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
    },
}));

Alpine.data('mesureForm', (catalogue, lignes, uniteParDefaut) => ({
    /*
     * Le formulaire de mesures ne propose plus des champs à remplir : le
     * tailleur clique sur l'icône d'une mesure du catalogue, et elle
     * s'ajoute à la liste des mesures à relever. Un second clic la retire.
     *
     * « catalogue » est indexé par code pour que l'ajout et le retrait se
     * fasse en O(1) : le nombre de mesures saisies est petit, mais la
     * palette affiche les 14 entrées du catalogue.
     */
    catalogue: catalogue ?? {},
    lignes: lignes ?? [],
    uniteParDefaut: uniteParDefaut || 'cm',

    get nombre() {
        return this.lignes.length;
    },

    entree(code) {
        return this.catalogue[code] ?? null;
    },

    contient(code) {
        return this.lignes.some((ligne) => ligne.code === code);
    },

    icone(code) {
        return this.entree(code)?.icone ?? 'fa-ruler-combined';
    },

    basculer(code) {
        if (this.contient(code)) {
            this.lignes = this.lignes.filter((ligne) => ligne.code !== code);

            return;
        }

        const mesure = this.entree(code);

        // Un code absent du catalogue ne correspond à aucune mesure à relever.
        if (!mesure) {
            return;
        }

        this.lignes.push({
            code: code,
            libelle: mesure.libelle,
            valeur: '',
            unite: this.uniteParDefaut,
        });

        this.$nextTick(() => this.allerAuChamp(this.lignes.length - 1));
    },

    /*
     * Le tailleur relève ses mesures en descendant la liste : chaque icône le
     * dépose directement sur le champ à remplir, sans qu'il ait à faire défiler
     * la page vers le bas pour y revenir, ni à remonter ensuite pour choisir la
     * mesure suivante.
     *
     * La ligne ajoutée étant la dernière, son index est connu sans avoir à
     * interroger le DOM, et « preventScroll » évite que le focus ne coupe la
     * animation de défilement amorcée juste avant.
     */
    allerAuChamp(index) {
        const champ = document.getElementById('valeur-' + index);

        if (!champ) {
            return;
        }

        champ.scrollIntoView({ behavior: 'smooth', block: 'center' });
        champ.focus({ preventScroll: true });
    },

    /*
     * Remise à zéro de la sélection. Le bouton « Annuler » du formulaire
     * d'ajout s'en sert : sans lui, abandonner une saisie en cours laissait
     * les icônes sélectionnées, et il fallait cliquer sur chacune pour les
     * retirer une à une.
     */
    vider() {
        this.lignes = [];

        const commentaire = document.getElementById('nouvelle-mesure-commentaire');

        if (commentaire) {
            commentaire.value = '';
        }

        this.$nextTick(() => this.allerAuxIcones());
    },

    /* Sens inverse : remonter à la palette pour choisir une autre mesure. */
    allerAuxIcones() {
        const palette = document.getElementById('palette-mesures');

        if (!palette) {
            return;
        }

        palette.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
}));

Alpine.start();

/*
 * Le sondage des notifications n'a besoin du DOM que pour lire l'adresse du
 * point d'état et l'identifiant de la dernière notification déjà affichée.
 * Vite injecte le script en différé, le document est donc normalement prêt ;
 * ce garde-fou couvre le cas où le navigateur l'exécuterait plus tôt.
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.store('notifications').demarrer();
    });
} else {
    Alpine.store('notifications').demarrer();
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Silencieux : l'application reste fonctionnelle hors-ligne ou non.
        });
    });
}
