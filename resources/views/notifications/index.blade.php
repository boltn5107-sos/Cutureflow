@extends('layouts.app')

@section('title', 'Notifications')
@section('header_title', 'Notifications')
@section('header_subtitle', 'Toutes les alertes reçues par votre compte')

@section('content')
    @php
        $onglets = [
            'toutes' => 'Toutes',
            'non_lues' => 'Non lues',
            'lues' => 'Lues',
        ];

        $filtreActif = in_array($filtres, array_keys($onglets), true) ? $filtres : 'toutes';

        $tonDefaut = 'bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200';

        $messageVide = match ($filtreActif) {
            'non_lues' => 'Vous avez lu toutes vos notifications.',
            'lues' => 'Aucune notification n\'a encore été lue.',
            default => 'Aucune notification pour le moment.',
        };
    @endphp

    <x-page-header
        title="Notifications"
        subtitle="Consultez, marquez comme lues ou supprimez vos alertes."
    >
        <x-slot:actions>
    <div class="grid grid-cols-2 gap-2 w-full sm:flex sm:flex-wrap sm:justify-end sm:w-auto">
        <a href="{{ route('notifications.read-all') }}" class="cf-btn-secondary w-full">
            <i class="fa-solid fa-check-double" aria-hidden="true"></i>
            <span class="hidden xs:inline">Tout marquer lu</span>
            <span class="xs:hidden">Marquer lu</span>
        </a>

        <div x-data class="w-full">
            <form method="POST" action="{{ route('notifications.clear') }}" x-ref="form" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <button
                type="button"
                class="cf-btn-danger w-full"
                @click="$store.confirm.ask({
                    title: @js('Supprimer toutes les notifications'),
                    message: @js('Cette action est définitive et vide votre centre de notifications. Continuer ?'),
                    confirmLabel: @js('Tout supprimer'),
                    tone: 'danger',
                    onConfirm: () => $refs.form.submit(),
                })"
            >
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                <span class="hidden xs:inline">Tout supprimer</span>
                <span class="xs:hidden">Tout supprimer</span>
            </button>
        </div>
    </div>
</x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2" role="group" aria-label="Filtrer les notifications">
        @php
            $classesOnglet = 'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition';
            $ongletActif = 'border-brand-800 bg-brand-800 text-brand-50 dark:border-brand-400 dark:bg-brand-400 dark:text-brand-900';
            $ongletInactif = 'border-brand-200 bg-white text-brand-700 hover:bg-brand-50 dark:border-white/10 dark:bg-white/5 dark:text-brand-200';
        @endphp

        @foreach ($onglets as $cle => $libelle)
            @php $compteur = match ($cle) {
                'non_lues' => $nonLues,
                'lues' => $lues,
                default => $nonLues + $lues,
            }; @endphp

            <a
                href="{{ route('notifications.index', ['filtre' => $cle]) }}"
                class="{{ $classesOnglet }} {{ $filtreActif === $cle ? $ongletActif : $ongletInactif }}"
                @if ($filtreActif === $cle) aria-current="page" @endif
            >
                {{ $libelle }}
                <span class="rounded-full bg-black/5 px-1.5 py-0.5 text-[0.65rem] tabular-nums dark:bg-white/10">
                    {{ number_format($compteur, 0, ',', ' ') }}
                </span>
            </a>
        @endforeach
    </div>

    <section class="cf-card overflow-hidden">
        <div class="cf-card-header">
            <h2 class="font-serif text-lg font-semibold">Centre de notifications</h2>
            <span class="text-xs text-brand-500 dark:text-brand-400">
                <span class="font-semibold tabular-nums">{{ number_format($nonLues + $lues, 0, ',', ' ') }}</span>
                notification(s)
            </span>
        </div>

        @if ($notifications->isEmpty())
            <x-empty-state
                icon="fa-regular fa-bell"
                title="Aucune notification"
                :message="$messageVide"
            />
        @else
            <ul class="divide-y divide-brand-100 dark:divide-white/5">
                @foreach ($notifications as $notification)
                    @php
                        $donnees = $notification->data ?? [];
                        $titre = $donnees['title'] ?? 'Notification';
                        $icone = $donnees['icon'] ?? 'fa-regular fa-bell';
                        $ton = $donnees['tone'] ?? $tonDefaut;
                        $estLue = $notification->read_at !== null;
                    @endphp

                    <li class="flex items-start gap-3 px-4 py-3 transition sm:px-5 {{ $estLue
                        ? 'bg-white dark:bg-brand-dark'
                        : 'bg-brand-50/70 dark:bg-white/[0.03]' }}">
                        <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg {{ $ton }}">
                            <i class="{{ $icone }} text-sm" aria-hidden="true"></i>
                        </span>

                        <a
                            href="{{ route('notifications.read', $notification->id) }}"
                            class="min-w-0 flex-1"
                            aria-label="{{ $titre }}{{ $estLue ? '' : ' (non lue)' }}"
                        >
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold">{{ $titre }}</span>

                                @unless ($estLue)
                                    <span class="size-2 shrink-0 rounded-full bg-brand-accent">
                                        <span class="sr-only">Non lue</span>
                                    </span>
                                @endunless
                            </span>

                            <span class="mt-0.5 block text-sm text-brand-600 dark:text-brand-300">
                                {{ $donnees['message'] ?? '' }}
                            </span>

                            <span class="mt-1 block text-[0.65rem] text-brand-400">
                                {{ $notification->created_at?->diffForHumans() }}
                            </span>
                        </a>

                        <div x-data class="shrink-0">
                            <form
                                method="POST"
                                action="{{ route('notifications.destroy', $notification->id) }}"
                                x-ref="form"
                                class="hidden"
                            >
                                @csrf
                                @method('DELETE')
                            </form>

                            <button
                                type="button"
                                class="flex size-8 items-center justify-center rounded-lg text-brand-500 transition hover:bg-red-50 hover:text-red-600 dark:text-brand-300 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                aria-label="Supprimer la notification : {{ $titre }}"
                                @click="$store.confirm.ask({
                                    title: @js('Supprimer la notification'),
                                    message: @js('Cette notification sera définitivement supprimée. Continuer ?'),
                                    confirmLabel: @js('Supprimer'),
                                    tone: 'danger',
                                    onConfirm: () => $refs.form.submit(),
                                })"
                            >
                                <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <x-pagination :paginator="$notifications" label="notifications" />
        @endif
    </section>
@endsection
