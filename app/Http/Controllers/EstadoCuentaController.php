<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Venta;
use App\Services\ClientScoringService;
include_once __DIR__ . '/../../Services/ClientScoringService.php';
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EstadoCuentaController extends Controller
{
    /**
     * Genera y descarga/visualiza el Estado de Cuenta en PDF para el cliente autenticado.
     */
    public function descargarPdfCliente(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        return $this->generarPdfParaUsuario($user);
    }

    /**
     * Genera y descarga/visualiza el Estado de Cuenta en PDF de cualquier cliente (para el Administrador).
     */
    public function descargarPdfAdmin(Request $request, User $cliente)
    {
        $ajuste = (float) $request->input('ajuste', 0);
        return $this->generarPdfParaUsuario($cliente, $ajuste);
    }

    /**
     * Lógica compartida para compilar los datos y renderizar el PDF oficial con QR.
     */
    protected function generarPdfParaUsuario(User $cliente, float $ajuste = 0)
    {
        // Usar la lógica centralizada y corregida del servicio
        $pdfPath = \App\Services\PdfReceiptService::generateEstadoCuentaPdf($cliente, $ajuste);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Estado_Cuenta_' . $cliente->name . '.pdf"'
        ]);
    }
}
