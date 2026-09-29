@props([
    'titre' => null,
    'texte' => '',
    'url' => null,
    'telephone' => null,
    'label' => 'Partager',
    'icon' => 'fa-solid fa-arrow-up-from-bracket',
    'class' => 'cf-btn-secondary',
])

@php
    /*
     * Les applications tierces n'acceptent que des numéros au format
     * international, alors que l'application saisit les numéros en format
     * local. Un numéro local sénégalais fait neuf chiffres et commence par
     * 7, sans préfixe interurbain : ajouter l'indicatif sur un « commence
     * par 0 » ne l'aurait jamais fait.
     *
     * Un numéro déjà international est détecté par son préfixe « 00 », par
     * un « + » saisi, ou par le fait de commencer par l'indicatif du pays.
     */
    $international = null;

    if (filled($telephone)) {
        $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';
        $indicatif = (string) config('coutureflow.indicatif_pays');

        if (filled($chiffres)) {
            if (str_starts_with(trim($telephone), '+')) {
                $international = $chiffres;
            } elseif (str_starts_with($chiffres, '00')) {
                $international = substr($chiffres, 2);
            } elseif ($indicatif !== '' && str_starts_with($chiffres, $indicatif)) {
                $international = $chiffres;
            } else {
                $international = $indicatif.$chiffres;
            }
        }
    }

    $options = [
        'titre' => $titre ?? config('app.name'),
        'texte' => $texte,
        'url' => $url ?? '',
        'telephone' => $international,
    ];
@endphp

<div
    x-data="partage({{ Js::from($options) }})"
    @keydown.escape.window="ouvert = false"
    @click.outside="ouvert = false"
    class="relative inline-flex"
>
    <button type="button" x-on:click="partager()" class="{{ $class }}">
        <i class="{{ $icon }}" aria-hidden="true"></i>
        {{ $label }}
    </button>

    <div
        x-cloak
        x-show="ouvert"
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-brand-200 bg-white p-1.5 shadow-elevated dark:border-white/10 dark:bg-brand-dark"
    >
        <p class="px-2.5 py-1.5 text-[0.65rem] font-semibold tracking-wide text-brand-500 uppercase">
            Envoyer vers
        </p>

        <a
            :href="lienWhatsapp"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i class="fa-brands fa-whatsapp w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
            WhatsApp
        </a>

        <a
            :href="lienFacebook"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i class="fa-brands fa-facebook w-4 text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
            Facebook
        </a>

        <a
            :href="lienTelegram"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i class="fa-brands fa-telegram w-4 text-sky-600 dark:text-sky-400" aria-hidden="true"></i>
            Telegram
        </a>

        <a
            :href="lienX"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i class="fa-brands fa-x-twitter w-4" aria-hidden="true"></i>
            X
        </a>

        <a
            :href="lienEmail"
            class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i class="fa-solid fa-envelope w-4 text-brand-400" aria-hidden="true"></i>
            Email
        </a>

        <button
            type="button"
            x-on:click="copier()"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm transition hover:bg-brand-50 dark:hover:bg-white/5"
        >
            <i
                class="fa-solid w-4"
                x-bind:class="copie ? 'fa-circle-check text-emerald-600 dark:text-emerald-400' : 'fa-copy text-brand-400'"
                aria-hidden="true"
            ></i>
            <span x-text="copie ? 'Lien copié' : 'Copier le lien'"></span>
        </button>
    </div>
</div>
