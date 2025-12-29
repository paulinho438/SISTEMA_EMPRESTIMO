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
        Commands\EnvioManualPagamentoMinimo::class,
        Commands\RecalcularParcelas::class,
        Commands\CobrancaAutomaticaABotao::class,
        Commands\CobrancaAutomaticaBBotao::class,
        Commands\CobrancaAutomaticaCBotao::class,
        Commands\CobrancaAutomaticaA::class,
        Commands\CobrancaAutomaticaB::class,
        Commands\CobrancaAutomaticaC::class,
        Commands\EnvioMensagemRenovacao::class,
        Commands\MensagemAutomaticaRenovacao::class,
        Commands\BackupClientes::class,
        Commands\ProcessarWebhookCobranca::class,
        Commands\RetirarProtestoEmprestimo::class,
        Commands\CorrigirDatasVencimentoFeriados::class

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
        $schedule->command('baixa:AutomaticaPagamentoMinimo')->everyMinute();

        $schedule->command('cobranca:AutomaticaABotao')->everyMinute();
        $schedule->command('cobranca:AutomaticaBBotao')->everyMinute();
        $schedule->command('cobranca:AutomaticaCBotao')->everyMinute();

        $schedule->command('mensagem:AutomaticaRenovacao')->everyMinute();
        $schedule->command('webhook:baixaBcodex')->everyMinute()->withoutOverlapping();

        //$schedule->command('recalcular:Parcelas')->dailyAt('00:00');

//        $schedule->command('recalcular:Parcelas')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('recalcular:Parcelas 0')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 1')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 2')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 3')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 4')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 5')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 6')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 7')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 8')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('recalcular:Parcelas 9')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('cobranca:AutomaticaA')->dailyAt('08:00');
//        $schedule->command('cobranca:AutomaticaA')->dailyAt('13:00');
        $schedule->command('cobranca:AutomaticaA')->dailyAt('15:10');
//        $schedule->command('cobranca:AutomaticaB')->dailyAt('13:00');
//        $schedule->command('cobranca:AutomaticaC')->dailyAt('16:30');

        // $schedule->command('rotinas:BackupClientes')->dailyAt('00:00');

        $schedule->command('protestar:Emprestimo')->dailyAt('07:00');
        $schedule->command('retirarProtesto:Emprestimo')->everyMinute()->withoutOverlapping();

        $schedule->command('rotina:envioMensagemRenovacao')->everyMinute()->withoutOverlapping();

        // Corrigir datas de vencimento que caem em feriados
        // $schedule->command('corrigir:datas-vencimento-feriados 0')->dailyAt('02:00')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 1')->dailyAt('02:05')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 2')->dailyAt('02:10')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 3')->dailyAt('02:15')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 4')->dailyAt('02:20')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 5')->dailyAt('02:25')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 6')->dailyAt('02:30')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 7')->dailyAt('02:35')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 8')->dailyAt('02:40')->withoutOverlapping();
        // $schedule->command('corrigir:datas-vencimento-feriados 9')->dailyAt('02:45')->withoutOverlapping();


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
