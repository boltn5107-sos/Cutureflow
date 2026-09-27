<?php

namespace App\Http\Controllers;

use App\Enums\DepenseCategorie;
use App\Http\Requests\DepenseRequest;
use App\Models\Depense;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DepenseController extends Controller
{
    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;
        $service = DashboardService::for($request->user());

        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : Carbon::now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : Carbon::now()->endOfMonth();

        $depenses = Depense::query()
            ->forAtelier($atelierId)
            ->with('creator')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->string('categorie')->toString()))
            ->whereBetween('date_depense', [$from, $to])
            ->orderByDesc('date_depense')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $total = (float) Depense::query()
            ->forAtelier($atelierId)
            ->whereBetween('date_depense', [$from, $to])
            ->sum('montant');

        return view('depenses.index', [
            'depenses' => $depenses,
            'total' => (float) $total,
            'repartition' => $service->repartitionDepenses($from->toDateString(), $to->toDateString()),
            'recettesPeriode' => $service->indicateurs($from->toDateString(), $to->toDateString())['recettes'],
            'categorieOptions' => DepenseCategorie::options(),
            'filtres' => $request->only('q', 'categorie', 'from', 'to'),
        ]);
    }

    public function create(): View
    {
        return view('depenses.create', [
            'categorieOptions' => DepenseCategorie::options(),
        ]);
    }

    public function store(DepenseRequest $request): RedirectResponse
    {
        $depense = Depense::create([
            ...$request->safe()->all(),
            'atelier_id' => $request->user()->atelier?->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('depenses.index')
            ->with('success', "La dépense « {$depense->libelle} » a été enregistrée.");
    }

    public function edit(Depense $depense): View
    {
        $this->authorize('update', $depense);

        return view('depenses.edit', [
            'depense' => $depense,
            'categorieOptions' => DepenseCategorie::options(),
        ]);
    }

    public function update(DepenseRequest $request, Depense $depense): RedirectResponse
    {
        $depense->update($request->safe()->all());

        return redirect()
            ->route('depenses.index')
            ->with('success', "La dépense « {$depense->libelle} » a été mise à jour.");
    }

    public function destroy(Depense $depense): RedirectResponse
    {
        $this->authorize('delete', $depense);

        $libelle = $depense->libelle;
        $depense->delete();

        return redirect()
            ->route('depenses.index')
            ->with('success', "La dépense « {$libelle} » a été supprimée.");
    }
}
