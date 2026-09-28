<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\RelanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Le tableau de bord porte toujours sur le mois en cours : le filtre de
     * dates a été retiré de l'interface. La lecture des paramètres « from »
     * et « to » a disparu avec lui, ainsi que le risque de Carbon::parse
     * sur une date non valide.
     *
     * DashboardService::indicateurs() conserve ses deux bornes facultatives,
     * utilisées par la caisse et les dépenses qui ont leur propre filtre.
     */
    public function index(Request $request): View
    {
        $service = DashboardService::for($request->user());

        return view('dashboard.index', [
            'indicateurs' => $service->indicateurs(),
            'dernieresCommandes' => $service->dernieresCommandes(),
            'prochainesLivraisons' => $service->commandesALivrerProchainement(),
            'commandesEnRetard' => $service->commandesEnRetard(),
            'prochainsRendezVous' => $service->prochainsRendezVous(),
            'evolution' => $service->evolutionFinanciere(),
            'activite' => $service->activiteRecente(),
            'topClients' => $service->topClients(),
            // Le compteur n'est calculé que si les relances sont activées :
            // la détection coûte plusieurs requêtes, inutile quand la
            // fonction reste masquée.
            'relances' => [
                'total' => config('coutureflow.relances_actives')
                    ? RelanceService::for($request->user())->compteur()
                    : 0,
            ],
        ]);
    }
}
