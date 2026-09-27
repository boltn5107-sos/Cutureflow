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
     * fassent en O(1) : le nombre de mesures saisies est petit, mais la
     * palette affiche les 23 entrées du catalogue.
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
    },
}));

Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Silencieux : l'application reste fonctionnelle hors-ligne ou non.
        });
    });
}
