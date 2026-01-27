<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Carregar o pacote XGate se ainda não foi carregado
        if (!class_exists('Account')) {
            $xgateIndexPath = base_path('vendor/xgate/xgate-integration/src/index.php');
            if (file_exists($xgateIndexPath)) {
                // Salvar o diretório atual
                $originalDir = getcwd();
                // Mudar para o diretório raiz do projeto Laravel
                chdir(base_path());
                
                // Incluir o arquivo
                require_once $xgateIndexPath;
                
                // Restaurar o diretório original
                chdir($originalDir);
            }
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
}
