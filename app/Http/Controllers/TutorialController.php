<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorialController extends Controller
{
    /**
     * Muestra el Manual de Usuario con el grid de tutoriales.
     */
    public function index()
    {
        return view('tutorial.index');
    }

    /**
     * Marca un tutorial como visto para el usuario autenticado (Llamado vía AJAX).
     */
    public function markAsSeen(Request $request)
    {
        $request->validate([
            'tutorial_name' => 'required|string'
        ]);

        $user = Auth::user();
        if ($user) {
            $user->tutorials()->firstOrCreate([
                'tutorial_name' => $request->tutorial_name
            ]);
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'unauthorized'], 401);
    }
}
