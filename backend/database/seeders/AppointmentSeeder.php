<?php
namespace Database\Seeders;

use App\Models\Appointment; 
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = ['Dr. Ionescu', 'Dr. Matei', 'Dr. Pop'];
        $statuses = ['Scheduled', 'Done', 'Cancelled'];
        
        $patients = [
            'Diana Forrai',
            'Bogdan Bargaoanu',
            'Andreea Marin',
            'Mihai Popescu',
            'Elena Ionescu',
            'Alexandru Georgescu',
            'Ioana Stan',
            'Cristian Radu',
            'Gabriela Dumitrescu',
            'Radu Florescu'
        ];
        
        for($i = 0; $i < 10; $i++) {
            Appointment::create([
                'patient_name' => $patients[$i],
                'doctor' => $doctors[array_rand($doctors)],
                'datetime' => Carbon::now()->addDays(rand(1, 14))->setTime(rand(8, 17), rand(0,3)*15),
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}