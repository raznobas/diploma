<?php

use App\Models\LeadAppointment;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('appointments:update-status', function () {
    $now = Carbon::now()->startOfDay();

    $updated = LeadAppointment::whereDate('training_date', '<', $now)
        ->where('status', 'scheduled')
        ->update(['status' => 'no_show']);

    Log::info('Lead appointments statuses updated.', ['updated_count' => $updated]);

    $this->info('Lead appointments statuses updated successfully.');
})->purpose('Update lead appointments status to no_show if training_date has passed and status was scheduled')->dailyAt('00:00');
