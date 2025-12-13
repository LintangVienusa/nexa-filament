<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncHoliday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    
    protected $signature = 'holiday:sync {year?}';
    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync holiday data from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        
        $now = Carbon::now('Asia/Jakarta');
        $year = $this->argument('year') ?? now()->year;

        $years = [$year];
        if ($now->month === 12) {
            $years[] = $year + 1;
        }



        foreach ($years as $syncYear) {
            $this->info("Sync holiday for year {$syncYear}");

            try {
                /**
                 * ============================
                 * API 1: api-harilibur (libur nasional)
                 * ============================
                 */
                $response = Http::timeout(15)
                    ->retry(3, 2000)
                    ->get("https://api-harilibur.vercel.app/api?year={$syncYear}");

                if ($response->successful()) {
                    foreach ($response->json() as $item) {
                        if($item['is_national_holiday'] === true){
                            DB::connection('mysql_employees')
                                ->table('Holiday')
                                ->updateOrInsert(
                                    [
                                        'holiday_date' => $item['holiday_date'],
                                    ],
                                    [
                                        'year' => Carbon::parse($item['holiday_date'])->year,
                                        'holiday_name' => $item['holiday_name'],
                                        'is_national_holiday' => true,
                                        'source' => 'api-harilibur',
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );
                        }
                    }
                    $this->info("Holiday sync completed for year {$syncYear} API 1: api-harilibur");
                }

                /**
                 * ============================
                 * API 2: dayoffapi (cuti bersama)
                 * ============================
                 */
                $response2 = Http::timeout(15)
                    ->retry(3, 2000)
                    ->get("https://dayoffapi.vercel.app/api?year={$syncYear}");

                if ($response2->successful()) {
                    foreach ($response2->json() as $item) {
                        DB::connection('mysql_employees')
                            ->table('Holiday')
                            ->updateOrInsert(
                                [
                                    'holiday_date' => $item['tanggal'],
                                ],
                                [
                                    'year' => Carbon::parse($item['tanggal'])->year,
                                    'holiday_name' => $item['keterangan'],
                                    'is_national_holiday' => $item['is_cuti'] ? 0 : 1,
                                    'source' => 'dayoffapi',
                                    'updated_at' => now(),
                                    'created_at' => now(),
                                ]
                            );
                    }
                    
                    $this->info("Holiday sync completed for year {$syncYear} API 2: dayoffapi (cuti bersama)");
                }

                

            } catch (\Throwable $e) {
                logger()->error('Holiday sync failed', [
                    'year' => $syncYear,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Holiday sync failed for year {$syncYear}");
            }
        }

        return Command::SUCCESS;
    }
}
