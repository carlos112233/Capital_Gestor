# 05 — Bitácora Viva de Deuda Técnica

> Bitácora viva de deuda técnica, riesgos de seguridad e inconsistencias arquitectónicas.
> Regla: Al resolver o encontrar un ítem, actualizar este documento citando `archivo:línea`.
> Convenciones: **[HECHO]** · **[INFERENCIA]** · **[DUDA]** · `❓ NO DETERMINABLE CON EL CÓDIGO`.

---

## Catálogo de Deuda Técnica

| ID | Severidad | Estado | Título | Archivo:Línea | Descripción Técnica |
|---|---|---|---|---|---|
| **DT-01** | **CRÍTICA** | `RESUELTA` | Backdoor RCE/Mantenimiento vía URL pública | `app/Providers/AppServiceProvider.php` | Removido completamente el bloque `comando_secreto`. Mantenimiento opera exclusivamente por CLI. |
| **DT-02** | **CRÍTICA** | `RESUELTA` | Descarga no autenticada de bitácora del sistema | `routes/web.php` | Extirpada la ruta pública `GET /descargar-log-secreto`. Logs accesibles únicamente por SSH/contenedor. |
| **DT-03** | **CRÍTICA** | `ABIERTA` | Credenciales de producción e historial en git | `.env.example:3,28,54-55`, `.env.txt`, `deploy_remote.py:8-12` | Claves activas expuestas: `APP_KEY`, contraseña SMTP de Gmail, scripts con credenciales SSH en texto plano y dumps de BD versionados. |
| **DT-04** | **CRÍTICA** | `ABIERTA` | Contraseñas en texto plano en cola de WhatsApp | `app/Http/Controllers/ClienteController.php:63,133,185-199` | Se insertan contraseñas sin hashear dentro del cuerpo de texto de `whatsapp_pending_messages`. |
| **DT-05** | **ALTA** | `RESUELTA` | Incompatibilidad de dialecto SQL (ILIKE en MySQL) | `app/Http/Controllers/Api/*` | Erradicado `ILIKE` en todos los controladores API (`VentaApi`, `ClienteApi`, `EntradaApi`, `CatalogoApi`, `PedidoApi`), estandarizado con `LOWER(...) LIKE ?` compatible con MySQL 8.0. |
| **DT-06** | **ALTA** | `ABIERTA` | Precios arbitrarios controlados por el cliente | `app/Http/Controllers/CatalogoController.php:53,84`, `VentaController.php:98,135` | La petición HTTP define el `precio_venta` que se cobra y almacena, en lugar de consultar el precio maestro de `articulos.precio`. |
| **DT-07** | **ALTA** | `ABIERTA` | Falta de rate limiting en formulario de login web | `app/Http/Controllers/Auth/AuthenticatedSessionController.php:27-30` | No utiliza `LoginRequest` ni middleware `throttle`, permitiendo ataques de fuerza bruta al panel web. |
| **DT-08** | **ALTA** | `ABIERTA` | Permisos permisivos e inseguros en despliegue Render | `docker-start.sh:11` | Comando `chmod -R 777` masivo sobre directorios públicos, almacenamiento y código fuente. |
| **DT-09** | **ALTA** | `ABIERTA` | Ausencia de índices en cola de alta concurrencia | `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:14-20` | `whatsapp_pending_messages` no tiene índices en `status` ni `created_at`, a pesar de ser consultada por polling cada 5 segundos por el motor de WhatsApp. |
| **DT-10** | **MEDIA** | `ABIERTA` | Inconsistencia de stock en registro de pedidos | `app/Http/Controllers/PedidoController.php:76-136` | `PedidoController@store` genera una `Venta` sin usar transacciones (`DB::transaction`) ni descontar existencias de `articulos.stock`. |
| **DT-11** | **MEDIA** | `ABIERTA` | Semillas de usuario con contraseñas triviales | `database/seeders/UsuariosSeeder.php:53-54` | Correos generados aleatoriamente con Faker y contraseñas fijas de 1 dígito (`$index % 8 + 1`), imposibilitando el acceso administrativo predecible. |
| **DT-12** | **MEDIA** | `ABIERTA` | Comprobantes bancarios expuestos en disco público | `app/Http/Controllers/ComprobanteController.php:29` | Se almacenan datos financieros sensibles en `public/storage/comprobantes` con acceso web directo no autenticado. |
| **DT-13** | **MEDIA** | `ABIERTA` | Riesgo de XSS por subida de vectores SVG | `app/Http/Controllers/BrandingController.php:82`, `ConfiguracionController.php:27` | Se permite el tipo MIME `svg` sin proceso de desinfección ni filtrado de etiquetas JavaScript. |
| **DT-14** | **MEDIA** | `ABIERTA` | Dependencia de artículo comodín no garantizado | `app/Http/Controllers/ComprobanteController.php:161-168` | Al aprobar comprobantes se busca `nombre LIKE '%pago saldado%'`; si no existe, la creación de `Entrada` arroja excepción SQL por `articulo_id` nulo. |
| **DT-15** | **MEDIA** | `ABIERTA` | Deuda artificial hardcodeada en PDFs | `app/Services/PdfReceiptService.php:149-151` | Asignación forzada de `$totalAdeudo = 4250.00` cuando no se detectan movimientos contables. |
| **DT-16** | **BAJA** | `ABIERTA` | Teléfonos de administrador hardcodeados en código | `app/Models/Venta.php:73,142`, `Feedback.php:85`, `PedidoController.php:106,355` | Número telefónico `'5212222153410'` acoplado directamente en múltiples clases como fallback. |
| **DT-17** | **BAJA** | `ABIERTA` | Rutas locales de desarrollo acopladas en wa-motor | `wa-motor/index.js:371,404` | Ruta `/home/araiza/.../qr.png` codificada fijamente para escribir la imagen QR. |
| **DT-18** | **BAJA** | `ABIERTA` | Textos residuales de marca previa en layouts | `resources/views/layouts/guest.blade.php:280` | Presencia del rótulo `HAMBURGUESAS & PANADERÍA` en vistas de autenticación. |
| **DT-19 (MP-05)** | **MEDIA** | `RESUELTA` | Contrato de cola WhatsApp y reintentos no formalizados | `app/Services/WhatsAppNotificationService.php`, `app/Console/Commands/ProcessWhatsAppQueueCommand.php`, `wa-motor/index.js` | Se centralizó el encolamiento Laravel, se documentó el consumidor Node y se agregó un comando de supervisión para reencolar fallos con intentos acotados, sin competir por los mensajes pendientes. |
