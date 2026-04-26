<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = ChargingSession::selectRaw('
                student_name,
                student_email,
                COUNT(*) as session_count,
                MAX(started_at) as last_session,
                MAX(CASE WHEN ended_at IS NULL THEN 1 ELSE 0 END) as is_active
            ')
            ->groupBy('student_email', 'student_name')
            ->orderByDesc('last_session')
            ->get();

        return view('students.index', compact('students'));
    }
}