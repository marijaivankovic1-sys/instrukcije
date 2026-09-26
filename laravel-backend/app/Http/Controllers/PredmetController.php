<?php

namespace App\Http\Controllers;

use App\Models\Predmet;
use Illuminate\Http\Request;

class PredmetController extends Controller
{
    public function index()
    {
        $predmeti = Predmet::all();

        return response()->json([
            'uspjeh' => true,
            'predmeti' => $predmeti
        ]);
    }
}