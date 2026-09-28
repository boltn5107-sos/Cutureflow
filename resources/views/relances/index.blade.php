@extends('layouts.app')

@section('title', 'Relances')
@section('header_title', 'Relances')
@section('header_subtitle', $total.' client(s) à rappeler')

@section('content')
    @php
        $joursOptions = [
            30 => '30 jours',
            60 => '60 jours',
            90 => '90 jours',
            180 => '6 mois',
        ];
    @endphp

    <x-page-header
        title="Relances"
        subtitle="À qui rappeler ? L'application vous indique les clients à contacter — aucun message n'est envoyé et aucune transaction n'est gérée."
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('relances.index') }}" class="flex items-end gap-2">
                <div class="w-36">
                    <x-form.select
                        name="jours"
                        label="Inactif depuis"
                        :options="$joursOptions"
                        :value="$jours"
                        :empty="false"
                    />
                </div>

                <button type="submit" class="cf-btn-secondary">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    Filtrer
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if ($total === 0)
        <div class="cf-card flex flex-col items-center gap-3 p-10 text-center">
            <i class="fa-solid fa-circle-check text-3xl text-emerald-500" aria-hidden="true"></i>
            <p class="font-serif text-lg font-semibold">Rien à relancer</p>
            <p class="max-w-md text-sm text-brand-600 dark:text-brand-300">
                Aucun client ne correspond à ces critères. Les clients déjà relancés
                disparaissent de la liste jusqu'à ce qu'ils redeviennent concernés.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($groupes as $motif => $groupe)
                @continue($groupe['clients']->isEmpty())

                <section class="cf-card overflow-hidden">
                    <header class="flex flex-wrap items-center gap-2 border-b border-brand-200 px-4 py-3.5 sm:px-5 dark:border-white/10">
                        <i class="{{ $groupe['icone'] }} text-brand-500" aria-hidden="true"></i>
                        <h2 class="font-serif text-base font-semibold">{{ $groupe['titre'] }}</h2>
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                            {{ $groupe['clients']->count() }}
                        </span>
                    </header>

                    {{-- data-label : le tableau bascule en cartes empilées sous 96rem. --}}
                    <div class="overflow-x-auto">
                        <table class="cf-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Téléphone</th>
                                    <th>Détail</th>
                                    <th>Depuis</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groupe['clients'] as $ligne)
                                    <tr data-label="Client">
                                        <td data-label="Client">
                                            <a
                                                href="{{ route('clients.show', $ligne['client']) }}"
                                                class="flex items-center gap-2.5 font-medium text-brand-900 hover:underline dark:text-brand-50"
                                            >
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-white/10 dark:text-brand-200">
                                                    {{ $ligne['client']->initiales() }}
                                                </span>
                                                <span class="truncate">{{ $ligne['client']->nom }}</span>
                                            </a>
                                        </td>
                                        <td data-label="Téléphone">
                                            @if ($ligne['client']->telephone)
                                                <a
                                                    href="tel:{{ preg_replace('/\s+/', '', $ligne['client']->telephone) }}"
                                                    class="inline-flex items-center gap-1.5 text-brand-600 hover:underline dark:text-brand-300"
                                                >
                                                    <i class="fa-solid fa-phone text-xs" aria-hidden="true"></i>
                                                    {{ $ligne['client']->telephone }}
                                                </a>
                                            @else
                                                <span class="text-brand-400">—</span>
                                            @endif
                                        </td>
                                        <td data-label="Détail" class="text-sm text-brand-600 dark:text-brand-300">
                                            {{ $ligne['detail'] }}
                                        </td>
                                        <td data-label="Depuis">
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap',
                                                'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' => $ligne['jours'] >= 60,
                                                'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => $ligne['jours'] < 60,
                                            ])>
                                                {{ $ligne['jours'] }} j
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" action="{{ route('relances.store') }}">
                                                    @csrf
                                                    <input type="hidden" name="client_id" value="{{ $ligne['client_id'] }}">
                                                    <input type="hidden" name="motif" value="{{ $motif }}">
                                                    <button type="submit" class="cf-btn-secondary cf-btn-sm">
                                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                        Relancé
                                                    </button>
                                                </form>

                                                <a href="{{ route('clients.show', $ligne['client']) }}" class="cf-btn-ghost cf-btn-sm">
                                                    Fiche
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
