<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable();
            $table->index(['status', 'expires_at']);
        });

        DB::table('requests')->select('id')->orderBy('id')->chunkById(200, function ($requests) {
            $firstDays = DB::table('request_days')->whereIn('request_id', $requests->pluck('id'))
                ->select('request_id')->selectRaw('MIN(day_date) as first_day')->groupBy('request_id')->get();
            foreach ($firstDays as $entry) {
                if ($entry->first_day) {
                    DB::table('requests')->where('id', $entry->request_id)->update([
                        'expires_at' => Carbon::parse($entry->first_day)->startOfDay()->subDays(5)->toDateTimeString(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
