<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->recalculate(0);
    }

    public function down(): void
    {
        $this->recalculate(5);
    }

    private function recalculate(int $daysBefore): void
    {
        DB::table('requests')->select('id')->chunkById(200, function ($requests) use ($daysBefore) {
            $firstDays = DB::table('request_days')->whereIn('request_id', $requests->pluck('id'))
                ->select('request_id')->selectRaw('MIN(day_date) as first_day')->groupBy('request_id')->pluck('first_day', 'request_id');
            foreach ($requests as $request) {
                $firstDay = $firstDays->get($request->id);
                DB::table('requests')->where('id', $request->id)->update([
                    'expires_at' => $firstDay ? Carbon::parse($firstDay)->startOfDay()->subDays($daysBefore)->toDateTimeString() : null,
                ]);
            }
        });
    }
};
