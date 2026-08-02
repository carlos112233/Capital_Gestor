<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return view('admin.configuracion.index');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension());
            $destinationPath = public_path('img');

            if ($extension === 'svg') {
                $file->move($destinationPath, 'Logo.svg');
            } else {
                $file->move($destinationPath, 'Logo.png');
                if (File::exists(public_path('img/Logo.svg'))) {
                    File::delete(public_path('img/Logo.svg'));
                }
            }

            return redirect()->back()->with('success', '¡El logotipo del sistema ha sido actualizado correctamente!');
        }

        return redirect()->back()->with('error', 'No se seleccionó ninguna imagen válida.');
    }
}
