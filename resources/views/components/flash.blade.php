@if (session('success') || session('error') || session('warning') || session('info'))
    <div class="mb-6 space-y-3">
        @foreach ([
            'success' => ['fa-solid fa-circle-check', 'text-emerald-700 dark:text-emerald-300', 'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30'],
            'error' => ['fa-solid fa-circle-exclamation', 'text-red-700 dark:text-red-300', 'bg-red-50 border-red-200 dark:bg-red-500/10 dark:border-red-500/30'],
            'warning' => ['fa-solid fa-triangle-exclamation', 'text-amber-800 dark:text-amber-300', 'bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/30'],
            'info' => ['fa-solid fa-circle-info', 'text-brand-800 dark:text-brand-200', 'bg-brand-50 border-brand-200 dark:bg-white/5 dark:border-white/10'],
        ] as $key => [$icon, $textClass, $boxClass])
            @if (session($key))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    role="alert"
                    class="flex items-start gap-3 rounded-xl border px-4 py-3.5 {{ $boxClass }}"
                >
                    <i class="{{ $icon }} mt-0.5 text-sm {{ $textClass }}" aria-hidden="true"></i>
                    <p class="flex-1 text-sm font-medium {{ $textClass }}">{!! session($key) !!}</p>
                    <button
                        type="button"
                        @click="show = false"
                        class="text-brand-400 transition hover:text-brand-700 dark:hover:text-brand-100"
                        aria-label="Fermer"
                    >
                        <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@endif

@if ($errors->any())
    {{--
        Résumé volontairement non détaillé : chaque erreur est déjà affichée
        sous le champ concerné. Les lister ici les affichait deux fois.
    --}}
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 dark:border-red-500/30 dark:bg-red-500/10" role="alert">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-sm text-red-700 dark:text-red-300" aria-hidden="true"></i>
        <div class="flex-1 text-sm text-red-700 dark:text-red-300">
            <p class="font-semibold">Le formulaire contient des erreurs.</p>
            <p class="mt-0.5 text-red-600 dark:text-red-400">
                Corrigez les champs signalés en rouge ci-dessous avant de renvoyer.
            </p>
        </div>
    </div>
@endif
