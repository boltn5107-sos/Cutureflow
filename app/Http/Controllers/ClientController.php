<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\FileStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function __construct(private readonly FileStorageService $files) {}

    public function index(Request $request): View
    {
        $atelierId = $request->user()->atelier?->id;

        $clients = Client::query()
            ->forAtelier($atelierId)
            ->withCount('commandes')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('etat'), fn ($q) => $q->where('is_active', $request->string('etat')->toString() === 'actif'))
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'filtres' => $request->only('q', 'etat'),
        ]);
    }

    public function create(): View
    {
        return view('clients.create', [
            'maxKb' => config('coutureflow.media.max_kb'),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $photo = $request->hasFile('photo')
            ? $this->files->storePrivate(
                $request->file('photo'),
                config('coutureflow.media.client_directory').'/'.$request->user()->atelier?->id
            )
            : null;

        $client = Client::create([
            'atelier_id' => $request->user()->atelier?->id,
            'nom' => $request->string('nom')->toString(),
            'telephone' => $request->string('telephone')->toString(),
            'email' => $request->input('email'),
            'adresse' => $request->input('adresse'),
            'notes' => $request->input('notes'),
            'is_active' => $request->boolean('is_active', true),
            'photo_disk' => $photo['disk'] ?? config('coutureflow.media.disk'),
            'photo_path' => $photo['path'] ?? null,
        ]);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', "Le client {$client->nom} a été ajouté avec succès.");
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load([
            'mesures' => fn ($q) => $q->latest('date_mesure')->latest('id')->limit(40),
            'commandes' => fn ($q) => $q->latest('date_commande')->limit(10),
        ]);

        return view('clients.show', [
            'client' => $client,
            'paiements' => $client->paiements()->limit(10)->get(),
            'rendezVous' => $client->rendezVous()->limit(5)->get(),
            'mesuresCourantes' => config('coutureflow.mesures_courantes', []),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', [
            'client' => $client,
            'maxKb' => config('coutureflow.media.max_kb'),
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $data = [
            'nom' => $request->string('nom')->toString(),
            'telephone' => $request->string('telephone')->toString(),
            'email' => $request->input('email'),
            'adresse' => $request->input('adresse'),
            'notes' => $request->input('notes'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('photo')) {
            $client->deletePhotoFile();

            $photo = $this->files->storePrivate(
                $request->file('photo'),
                config('coutureflow.media.client_directory').'/'.$request->user()->atelier?->id
            );

            $data['photo_disk'] = $photo['disk'];
            $data['photo_path'] = $photo['path'];
        } elseif ($request->boolean('remove_photo') && $client->hasPhoto()) {
            $client->deletePhotoFile();
            $data['photo_path'] = null;
        }

        $client->update($data);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', "Les informations de {$client->nom} ont été mises à jour.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $nom = $client->nom;
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', "Le client {$nom} a été supprimé.");
    }

    /**
     * Sert la photo du client via un controller autorisé.
     * Le fichier est stocké hors de public/ : aucune URL directe n'est accessible.
     */
    public function photo(Client $client): StreamedResponse
    {
        $this->authorize('viewPhoto', $client);

        abort_unless($client->hasPhoto(), 404);

        $disk = Storage::disk($client->photo_disk ?: config('coutureflow.media.disk'));
        abort_unless($disk->exists($client->photo_path), 404);

        return $disk->response(
            $client->photo_path,
            basename($client->photo_path),
            [
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
