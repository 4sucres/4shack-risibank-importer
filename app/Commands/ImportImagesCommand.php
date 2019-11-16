<?php

namespace App\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ImportImagesCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import:images';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Step 2: Import images from NoelShack';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $noelshackImages = DB::table('noelshack_images')
            ->where('image_id', null)
            ->orderBy('risibank_id', 'asc');

        $bar = $this->output->createProgressBar($noelshackImages->count());

        $noelshackImages->chunk(200, function ($chunk) use ($bar) {
            $chunk->each(function ($noelshackImage) use ($bar) {
                $fileName = str_replace('/', '-', $noelshackImage->url);

                $image = DB::table('images')
                    ->where('path', $fileName)
                    ->first();

                if ($image) {
                    // File was duplicated :sad:
                    DB::table('noelshack_images')
                        ->where('id', $noelshackImage->id)
                        ->update([
                            'image_id' => $image->id,
                            'updated_at' => now(),
                        ]);

                    return;
                }

                try {
                    $content = file_get_contents('https://image.noelshack.com/fichiers/' . $noelshackImage->url);
                } catch (\Throwable $th) {
                    // File was not found on NoelShack, try with the RisiBank cache
                    try {
                        $content = file_get_contents('https://risibank.fr/cache/stickers/' . $noelshackImage->risibank_cache_url);
                    } catch (\Throwable $th) {
                        // File was not found :sad:
                        DB::table('noelshack_images')
                            ->where('id', $noelshackImage->id)
                            ->delete();

                        return;
                    }
                }

                if (Storage::put($fileName, $content, 'public')) {
                    $imageId = DB::table('images')
                        ->insertGetId([
                            'name' => $fileName,
                            'path' => $fileName,
                            'size' => Storage::size($fileName),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    DB::table('noelshack_images')
                        ->where('id', $noelshackImage->id)
                        ->update([
                            'image_id' => $imageId,
                            'updated_at' => now(),
                        ]);
                }

                $bar->advance();
            });
        });

        $bar->finish();
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
