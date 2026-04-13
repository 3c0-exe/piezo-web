<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students        = Student::orderBy('name')->get();
        $activeStudentId = SystemSetting::current()->active_student_id;

        return view('students.index', compact('students', 'activeStudentId'));
    }

    public function create(): View
    {
        return view('students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'student_id' => 'required|string|max:50|unique:students,student_id',
            'section'    => 'required|string|max:100',
            'year_level' => 'required|string|max:100',
        ], [
            'student_id.unique' => 'This Student ID is already registered.',
        ]);

        Student::create($validated);

        return redirect()
            ->route('students.index')
            ->with('success', "Student \"{$validated['name']}\" registered successfully.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $settings = SystemSetting::current();

        // If deleting the active student, clear the active slot first
        if ($settings->active_student_id === $student->id) {
            $settings->update([
                'active_student_id'  => null,
                'is_tracking_on'     => false,
                'tracking_started_at'=> null,
            ]);
        }

        $name = $student->name;
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', "Student \"{$name}\" has been removed.");
    }
}
