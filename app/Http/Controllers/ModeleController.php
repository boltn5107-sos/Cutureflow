<?php

namespace App\Http\Controllers;

use App\Enums\ModeleCategorie;
use App\Http\Requests\ModeleRequest;
use App\Models\Modele;
use App\Models\ModelePhoto;
use App\Services\ModelePhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModeleController extends Controller
{
    public function __construct(private readonly ModelePhotoService $photos) {}

    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        $modeles = Modele::query()
            ->forAtelier($atelierId)
            ->with('photos')
            ->withCount('commandes')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('categorie'), fn ($q) => $q->categorie($request->string('categorie')->toString()))
            ->orderBy('categorie')
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        $compteurs = Modele::forAtelier($atelierId)
            ->selectRaw('categorie, COUNT(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie')
            ->all();

        return view('catalogue.index', [
            'modeles' => $modeles,
            'categorieOptions' => ModeleCategorie::options(),
            'filtres' => $request->only('q', 'categorie'),
            'compteurs' => $compteurs,
        ]);
    }

    public function create(): View
    {
        return view('catalogue.create', [
            'categorieOptions' => ModeleCategorie::options(),
            'maxKb' => config('coutureflow.media.max_kb'),
        ]);
    }

    public function store(ModeleRequest $request): RedirectResponse
    {
        $modele = Modele::create([
            ...$request->safe()->except('photos'),
            'atelier_id' => $request->user()->atelier?->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->hasFile('photos')) {
            $this->photos->store($modele, $request->file('photos'));
        }

        return redirect()
            ->route('modeles.show', $modele)
            ->with('success', "Le modèle « {$modele->nom} » a été ajouté au catalogue.");
    }

    public function show(Modele $modele): View
    {
        $this->authorize('view', $modele);

        $modele->load(['photos', 'commandes' => fn ($q) => $q->with('client')->limit(5)]);

        return view('catalogue.show', [
            'modele' => $modele,
        ]);
    }

    public function edit(Modele $modele): View
    {
        $this->authorize('update', $modele);

        $modele->load('photos');

        return view('catalogue.edit', [
            'modele' => $modele,
            'categorieOptions' => ModeleCategorie::options(),
            'maxKb' => config('coutureflow.media.max_kb'),
        ]);
    }

    public function update(ModeleRequest $request, Modele $modele): RedirectResponse
    {
        $this->authorize('update', $modele);

        $modele->update([
            ...$request->safe()->except('photos'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->hasFile('photos')) {
            $position = $modele->photos()->max('position') ?? -1;
            $this->photos->store($modele, $request->file('photos'), $position + 1);
        }

        return redirect()
            ->route('modeles.show', $modele)
            ->with('success', "Le modèle « {$modele->nom} » a été mis à jour.");
    }

    public function destroy(Modele $modele): RedirectResponse
    {
        $this->authorize('delete', $modele);

        $nom = $modele->nom;
        $this->photos->deleteAll($modele);
        $modele->delete();

        return redirect()
            ->route('catalogue.index')
            ->with('success', "Le modèle « {$nom} » a été supprimé du catalogue.");
    }

    public function destroyPhoto(Modele $modele, ModelePhoto $photo): RedirectResponse
    {
        $this->authorize('update', $modele);

        abort_unless($photo->modele_id === $modele->id, 404);

        $this->photos->delete($photo);

        return back()->with('success', 'La photo a été supprimée.');
    }

    public function reorderPhotos(Modele $modele, Request $request): RedirectResponse
    {
        $this->authorize('update', $modele);

        $validated = $request->validate([
            'ordre' => ['required', 'array'],
            'ordre.*' => ['integer', 'min:0'],
        ]);

        $this->photos->reorder($modele, $validated['ordre']);

        return back()->with('success', "L'ordre des photos a été mis à jour.");
    }

    /**
     * Sert une photo de modèle via un controller autorisé.
     */
    public function photo(ModelePhoto $photo): StreamedResponse
    {
        $this->authorize('viewPhoto', $photo);

        return $this->photos->stream($photo);
    }
}
