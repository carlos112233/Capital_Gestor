<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\Process\Process;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $mensaje;

    public function __construct(User $user, $mensaje)
    {
        $this->user = $user;
        $this->mensaje = $mensaje;
    }

    public function handle()
    {
        // 1. Iniciar el Driver manualmente (Esto evita el error de afterClass)
        $driverPath = base_path('vendor/laravel/dusk/bin/chromedriver-linux');
        $process = new Process([$driverPath, '--port=9515']);
        $process->start();

        sleep(2); // Esperar a que el driver abra

        $options = (new ChromeOptions)->addArguments([
            '--user-data-dir=' . storage_path('app/whatsapp_session'),
            '--headless', // Invisible para que el servidor no sufra
            '--no-sandbox',
            '--disable-dev-shm-usage',
        ]);

        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);

        try {
            $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);
            $browser = new Browser($driver);

            $numero = preg_replace('/[^0-9]/', '', $this->user->telefono);
            if (strlen($numero) == 10) $numero = '52' . $numero;

            $browser->visit("https://web.whatsapp.com/send?phone=$numero&text=" . urlencode($this->mensaje));
            // ... después de visitar la URL
            $inputSelector = 'div[contenteditable="true"]';
            $browser->waitFor($inputSelector, 45);

            // 1. Forzar clic para dar foco
            $browser->click($inputSelector);
            $browser->pause(2000); // Esperar 2 segundos a que el cursor parpadee

            // 2. Escribir un espacio o asegurar que el texto está ahí
            // A veces el texto de la URL no se pega bien, esto asegura que haya algo
            $browser->keys($inputSelector, " ");
            $browser->pause(1000);

            // 3. Presionar ENTER
            $browser->keys($inputSelector, \Facebook\WebDriver\WebDriverKeys::ENTER);

            // 4. AUMENTAR EL TIEMPO DE ESPERA FINAL
            // Si el servidor es lento, necesita tiempo para subir el mensaje
             \Log::info("Esperando que el mensaje salga del servidor...");
            sleep(8);
            $browser->keys($inputSelector, \Facebook\WebDriver\WebDriverKeys::ENTER);

            sleep(5); // Pausa para que el mensaje salga

            \Log::info("✅ WhatsApp enviado desde la web a: " . $this->user->name);
        } catch (\Exception $e) {
            \Log::error("❌ Error enviando desde la web a {$this->user->name}: " . $e->getMessage());
        } finally {
            if (isset($driver)) $driver->quit();
            $process->stop(); // Matar el proceso del driver
        }
    }
}
