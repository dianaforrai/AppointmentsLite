<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $base = $now->copy()->startOfHour();

        $doctors  = ['Dr. Ionescu', 'Dr. Pop', 'Dr. Matei'];
        $statuses = ['Scheduled', 'Done', 'Cancelled'];

        $patients = [
            'Ana Popescu', 'Mihai Ionescu', 'Elena Matei',
            'Andrei Stoica', 'Ioana Dinu', 'Vlad Marinescu',
            'Carmen Radu', 'George Pavel', 'Roxana Preda',
        ];

        $rows = [];
        for ($i = 0; $i < 9; $i++) {
            $rows[] = [
                'patient_name' => $patients[$i],
                'doctor'       => $doctors[$i % count($doctors)],
                'datetime'     => $base->copy()->addDays($i - 3)->addHours(($i % 5) + 8),
                'status'       => $statuses[$i % count($statuses)], // Scheduled, Done, Cancelled
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        DB::table('appointments')->insert($rows);
    }
}