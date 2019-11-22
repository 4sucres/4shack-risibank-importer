<?php

namespace App\Commands;

use Goutte\Client;
use App\Helpers\SlackNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;

class ImportRisiBankCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import:risibank {--then-import-images}';

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
        $this->notify('4shack is importing the RisiBank...' . "\r\n" . 'https://media.discordapp.net/attachments/636595347317325829/637409348183785512/k2wYUx3.gif');

        $failedTries = 0;
        $triesTreshold = 100;
        $importedImages = 0;

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
            $importedImages++;
        }

        $this->info($importedImages . ' added into database');
        $this->notify($importedImages . ' added into database 👍');

        if ($this->option('then-import-images')) {
            $this->call('import:images');
        }
    }

    public function notify($message)
    {
        SlackNotification::send(
            now()->format('d/m/Y H:i:s') . ' : ' . $message
        );
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
