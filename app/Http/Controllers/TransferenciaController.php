<?php
namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\NuevoPedidoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException; // Importación necesaria

class TransferenciaController extends Controller
{
    public function index(Request $request)
    {
         return view('transferencia.index');
    }
}