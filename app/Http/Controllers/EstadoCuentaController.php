<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ClientScoringService;
use App\Services\PdfReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstadoCuentaController extends Controller
{
    public function __construct(
        protected ClientScoringService $scoringService,
        protected PdfReceiptService $pdfService
    ) {}

    /**
     * Genera y descarga/visualiza el Estado de Cuenta en PDF para el cliente autenticado.
     */
    public function descargarPdfCliente(Request $request): BinaryFileResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        return $this->generarPdfParaUsuario($user);
    }

    /**
     * Genera y descarga/visualiza el Estado de Cuenta en PDF de cualquier cliente (para el Administrador).
     */
    public function descargarPdfAdmin(Request $request, User $cliente): BinaryFileResponse
    {
        $ajuste = (float) $request->input('ajuste', 0);
        return $this->generarPdfParaUsuario($cliente, $ajuste);
    }

    /**
     * Lógica compartida para compilar los datos y renderizar el PDF oficial con QR.
     */
    protected function generarPdfParaUsuario(User $cliente, float $ajuste = 0): BinaryFileResponse
    {
        $pdfPath = $this->pdfService->generateEstadoCuentaPdf($cliente, $ajuste);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Estado_Cuenta_' . $cliente->name . '.pdf"',
        ]);
    }
}
