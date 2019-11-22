<?php

namespace App\Commands;

use Goutte\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ImportRisiBankCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import:risibank {--then-import-images} {--then}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Step 1: Import urls from RisiBank';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $failedTries = 0;
        $triesTreshold = 100;

        // Get latest id
        $id = DB::table('noelshack_images')
            ->select('risibank_id')
            ->orderBy('risibank_id', 'desc')
            ->first()
            ->risibank_id ?? 0;

        $this->info('Resuming from #' . $id);

        $client = new Client();

        while ($failedTries < $triesTreshold) {
            ++$id;

            $crawler = $client->request('GET', 'https://risibank.fr/stickers/' . $id . '-' . uniqid());

            if (
                $client->getResponse()->getStatus() != 200
                || $crawler->filter('input[name="search"]')->count()
            ) {
                ++$failedTries;
                $this->line('#' . $id . ' does not exists (failed: ' . $failedTries . ')');
                continue;
            } else {
                // Reset failed counter
                $failedTries = 0;
            }

            $noelshackUrl = $crawler->filter('tbody>tr')
                ->eq(1)
                ->filter('td')
                ->eq(1)
                ->text();
            $noelshackUrl = trim($noelshackUrl);

            $risibankCacheUrl = $crawler->filter('.img-preview-big')
                ->attr('src');
            $risibankCacheUrl = trim($risibankCacheUrl);
            $risibankCacheUrl = str_replace('https://risibank.fr/cache/stickers/', '', $risibankCacheUrl);

            DB::table('noelshack_images')->insert([
                'url' => $noelshackUrl,
                'risibank_id' => $id,
                'risibank_cache_url' => $risibankCacheUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->line('#' . $id . ' saved into database');
        }

        if ($this->option('then-import-images')) {
            $this->call('import:images');
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
