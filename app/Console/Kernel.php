<?php

namespace App\Console;

use App\MessengerUser;
use \BotMan\Drivers\Telegram\TelegramDriver;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {

            $botman = resolve('botman');
            $users = MessengerUser::where('user_agree', true)->select('id_chat')->get()->keyBy('id_chat')->keys()->toArray();
            $botman->say('Проверочное сообщение каждую минуту', $users, TelegramDriver::class);

            //mail('mail@to.com', 'mail@out.com', 'Hello');
        })->everyMinute();
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
