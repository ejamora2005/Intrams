<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->value());
        $courses = Course::query()
            ->withCount('students')
            ->when($search !== '', fn ($query) => $query->where(fn ($courseQuery) => $courseQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.courses.index', compact('courses', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $course = Course::create(['name' => Str::title(Str::squish(strip_tags($data['name']))), 'code' => 'CRS-'.Str::upper(Str::random(8)), 'status' => 'active']);
        app(\App\Services\AuditService::class)->record('course.created', $course, null, $course->only($course->getFillable()));

        return back()->with('success', 'Course created.');
    }
}
