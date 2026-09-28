<?php

namespace App\Http\Controllers;

use App\Http\Requests\RelanceRequest;
use App\Models\Client;
use App\Models\ClientRelance;
use App\Services\RelanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelanceController extends Controller
{
    public function index(Request $request): View
    {
        $jours = RelanceRequest::joursInactif($request->integer('jours') ?: null);
        $groupes = RelanceService::for($request->user())->suggestions($jours);

        // Les clients qui justifient deux motifs n'apparaissent qu'une fois,
        // dans le groupe le plus urgent : on additionne les groupes tels
        // quels, le total affiché et le nombre de lignes concordent donc.
        $total = collect($groupes)->sum(fn (array $groupe) => $groupe['clients']->count());

        return view('relances.index', [
            'groupes' => $groupes,
            'jours' => $jours,
            'total' => $total,
        ]);
    }

    /**
     * Enregistre que l'atelier a rappelé ce client : il sort de la liste.
     * L'application n'envoie aucun message — la relance se fait par l'appel
     * que le tailleur passe depuis son téléphone.
     */
    public function store(RelanceRequest $request): RedirectResponse
    {
        $client = Client::forAtelier($request->user()->atelierId())
            ->findOrFail($request->integer('client_id'));

        ClientRelance::create([
            'client_id' => $client->id,
            'atelier_id' => $client->atelier_id,
            'user_id' => $request->user()->id,
            'motif' => $request->input('motif', 'manuel'),
            'note' => $request->input('note'),
        ]);

        return back()->with('succes', 'Relance enregistrée pour '.$client->nom.'.');
    }
}
