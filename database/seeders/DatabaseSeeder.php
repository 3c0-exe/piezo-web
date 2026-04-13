<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin user ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@piezo.local'],
            [
                'name'     => 'Piezo Admin',
                'password' => Hash::make('piezo@admin2024'),
            ]
        );

        // ── System settings singleton ────────────────────────────────
        SystemSetting::updateOrCreate(
            ['id' => 1],
            [
                'active_student_id'  => null,
                'is_tracking_on'     => false,
                'tracking_started_at'=> null,
            ]
        );

        // ── Sample students ──────────────────────────────────────────
        $students = [
            [
                'name'       => 'Juan dela Cruz',
                'student_id' => '2024-00001',
                'section'    => 'Sec-A',
                'year_level' => 'Grade 11',
            ],
            [
                'name'       => 'Maria Santos',
                'student_id' => '2024-00002',
                'section'    => 'Sec-B',
                'year_level' => 'Grade 11',
            ],
            [
                'name'       => 'Jose Reyes',
                'student_id' => '2024-00003',
                'section'    => 'Sec-A',
                'year_level' => 'Grade 12',
            ],
        ];

        foreach ($students as $data) {
            Student::updateOrCreate(
                ['student_id' => $data['student_id']],
                $data
            );
        }
    }
}
