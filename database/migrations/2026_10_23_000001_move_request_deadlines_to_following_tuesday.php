<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->recalculate(true);
    }

    public function down(): void
    {
        $this->recalculate(false);
    }

    private function recalculate(bool $followingTuesday): void
    {
        DB::table('requests')->orderBy('id')->chunkById(500, function ($requests) use ($followingTuesday) {
            foreach ($requests as $request) {
                $firstDay = DB::table('request_days')->where('request_id', $request->id)->min('day_date');
                $deadline = $firstDay ? Carbon::parse($firstDay)->startOfDay() : null;
                if ($deadline && $followingTuesday) {
                    $deadline->startOfWeek(Carbon::MONDAY)->addWeek()->addDay();
                }
                DB::table('requests')->where('id', $request->id)->update(['expires_at' => $deadline?->toDateTimeString()]);
            }
        });
    }
};
