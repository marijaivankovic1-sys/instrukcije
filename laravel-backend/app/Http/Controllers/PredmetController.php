<?php

namespace App\Http\Controllers;

use App\Models\Predmet;
use Illuminate\Http\Request;

class PredmetController extends Controller
{
    public function index()
    {
        return response()->json([
            'uspjeh' => true,
            'predmeti' => Predmet::all()
        ]);
    }

    public function show($id)
    {
        $predmet = Predmet::find($id);

        if (!$predmet) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'predmet' => $predmet
        ]);
    }

    public function store(Request $request)
    {
        if (empty($request->naziv)) {
            return response()->json([
                'message' => 'Naziv predmeta je obavezan.'
            ], 422);
        }

        $predmet = Predmet::create([
            'naziv' => trim($request->naziv),
            'opis' => isset($request->opis) ? trim($request->opis) : ''
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno dodan.',
            'id' => $predmet->id
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $predmet = Predmet::find($id);

        if (!$predmet) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        if (empty($request->naziv)) {
            return response()->json([
                'message' => 'Naziv predmeta je obavezan.'
            ], 422);
        }

        $predmet->update([
            'naziv' => trim($request->naziv),
            'opis' => isset($request->opis) ? trim($request->opis) : ''
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno uređen.'
        ]);
    }

    public function destroy($id)
    {
        $predmet = Predmet::find($id);

        if (!$predmet) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        $predmet->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Predmet je uspješno obrisan.'
        ]);
    }
}