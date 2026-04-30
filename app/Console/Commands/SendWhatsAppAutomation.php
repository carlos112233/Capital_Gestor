<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\SupportsChrome; // <-- Importante
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Laravel\Dusk\Chrome\ChromeProcess;

class SendWhatsAppAutomation extends Command
{
    protected $signature = 'wa:send {numero} {mensaje}'; // <--- REVISA ESTA LÍNEA

    /**
     * La descripción del comando.
     */
    protected $description = 'Envía un mensaje de WhatsApp automatizado';
    public function handle()
    {
        // 1. Iniciar el proceso del Driver automáticamente
        $process = (new ChromeProcess)->toProcess();
        $process->start();

        $numero = $this->argument('numero');
        $mensaje = $this->argument('mensaje');

        $options = (new ChromeOptions)->addArguments([
            '--user-data-dir=' . storage_path('app/whatsapp_session'),
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--headless',
        ]);

        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);

        try {
            // 2. IMPORTANTE: Usar el puerto 9515 que es el que abre ChromeProcess por defecto
            $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);
            $browser = new Browser($driver);
            $this->info("Navegador iniciado. Entrando a WhatsApp...");

            // 1. Entrar a la URL
            $url = "https://web.whatsapp.com/send?phone=$numero&text=" . urlencode($mensaje);
            $browser->visit($url);

            $this->warn("⚠️  Asegúrate de haber escaneado el QR. Esperando carga del chat...");

            // 2. En lugar de buscar el botón de enviar, buscamos el CUADRO DE TEXTO
            // WhatsApp usa este selector para el input donde escribes
            $inputSelector = 'div[contenteditable="true"]';
            $this->info("Conectado al navegador. Enviando mensaje...");

            // Tu lógica de WhatsApp aquí...
            $browser->visit("https://web.whatsapp.com/send?phone=$numero&text=" . urlencode($mensaje));

            // Esperar al botón de enviar (Damos 60s por si hay que escanear el QR)
            try {
                // Esperamos hasta 60 segundos a que el chat esté listo
                $browser->waitFor($inputSelector, 60);

                $this->info("¡Chat cargado! Enviando...");

                // 3. Hacemos clic en el cuadro de texto para asegurar el foco
                $browser->click($inputSelector);
                $browser->pause(1000);

                // 4. Presionamos la tecla ENTER (WhatsApp envía el mensaje con Enter)
                $browser->keys($inputSelector, \Facebook\WebDriver\WebDriverKeys::ENTER);

                $this->info("✅ Mensaje enviado con éxito.");
            } catch (\Exception $e) {
                // Si falla, tomamos una foto para ver qué está viendo el navegador
                $screenshotName = 'error_whatsapp_' . time();
                $browser->screenshot($screenshotName);
                $this->error("❌ El chat no cargó o el selector cambió. Foto guardada como: $screenshotName.png");

                // TIP EXTRA: A veces WhatsApp cambia el selector. 
                // Intentemos un último recurso: presionar Enter a ciegas
                $this->warn("Intentando envío a ciegas...");
                $browser->pause(5000);
                $browser->keys('', \Facebook\WebDriver\WebDriverKeys::ENTER);
            }

            $browser->pause(2000);
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        } finally {
            // 3. Cerrar todo correctamente
            if (isset($driver)) {
                $driver->quit();
            }
        }
    }
}
