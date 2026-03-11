<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        // 作成したコマンドをここに登録
        Commands\AiTalkFirst::class,
        Commands\LonelyTick::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // 毎分コマンドを実行
        $schedule->command('ai:lonely-tick')->everyMinute();
        $schedule->command('ai:talk-first')->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}