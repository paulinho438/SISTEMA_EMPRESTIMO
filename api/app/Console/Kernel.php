<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\EnvioManual::class,
        Commands\EnvioManualQuitacao::class,
        Commands\RecalcularParcelas::class,
        Commands\CobrancaAutomaticaA::class,
        Commands\CobrancaAutomaticaB::class,
        Commands\CobrancaAutomaticaC::class,
    ];


    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('baixa:Automatica')->everyMinute();
        $schedule->command('baixa:AutomaticaQuitacao')->everyMinute();

        $schedule->command('recalcular:Parcelas')->dailyAt('00:00');

        $schedule->command('cobranca:AutomaticaA')->weekdays()->dailyAt('08:00');
        $schedule->command('cobranca:AutomaticaB')->weekdays()->dailyAt('13:00');
        $schedule->command('cobranca:AutomaticaC')->weekdays()->dailyAt('16:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
