<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CoordinatorController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\Admin\EditionSportController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\Admin\SportModuleController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return auth()->user()->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('coordinator.dashboard');
    })->name('dashboard');

    Route::prefix('admin')->as('admin.')->middleware(['role:admin', 'return.admin.index'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/editions', [EditionController::class, 'index'])->name('editions.index');
        Route::get('/editions/create', [EditionController::class, 'create'])->name('editions.create');
        Route::post('/editions', [EditionController::class, 'store'])->name('editions.store');
        Route::get('/editions/{edition}/edit', [EditionController::class, 'edit'])->name('editions.edit');
        Route::put('/editions/{edition}', [EditionController::class, 'update'])->name('editions.update');
        Route::delete('/editions/{edition}', [EditionController::class, 'destroy'])->name('editions.destroy');
        Route::get('/editions/{edition}/sports/create', [EditionSportController::class, 'create'])->name('editions.sports.create');
        Route::post('/editions/{edition}/sports', [EditionSportController::class, 'store'])->name('editions.sports.store');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/students/{student}/restore', [StudentController::class, 'restore'])->name('students.restore');
        Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::get('/teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
        Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('/teams/{team}/restore', [TeamController::class, 'restore'])->name('teams.restore');
        Route::post('/teams/{team}/members', [TeamController::class, 'assignMember'])->name('teams.members.store');
        Route::post('/teams/{team}/members/bulk-remove', [TeamController::class, 'removeMembers'])->name('teams.members.bulk-remove');
        Route::delete('/teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');
        Route::get('/coordinators', [CoordinatorController::class, 'index'])->name('coordinators.index');
        Route::get('/coordinators/create', [CoordinatorController::class, 'create'])->name('coordinators.create');
        Route::post('/coordinators', [CoordinatorController::class, 'store'])->name('coordinators.store');
        Route::get('/coordinators/{coordinator}/edit', [CoordinatorController::class, 'edit'])->name('coordinators.edit');
        Route::put('/coordinators/{coordinator}', [CoordinatorController::class, 'update'])->name('coordinators.update');
        Route::post('/coordinators/{coordinator}/assignments', [CoordinatorController::class, 'assign'])->name('coordinators.assignments.store');
        Route::delete('/coordinators/{coordinator}/assignments/{assignment}', [CoordinatorController::class, 'revoke'])->name('coordinators.assignments.destroy');
        Route::post('/coordinators/{coordinator}/reset-device', [CoordinatorController::class, 'resetDevice'])->name('coordinators.device.reset');
        Route::post('/coordinator-requests/{coordinatorRequest}/review', [CoordinatorController::class, 'reviewRequest'])->name('coordinator-requests.review');
        Route::get('/sports-events', fn () => redirect()->route('admin.events.index'))->name('sports-events.index');
        Route::resource('sports', SportController::class)->except(['show']);
        Route::get('/sports/{sport}/bracket', [SportController::class, 'bracket'])->name('sports.bracket');
        Route::get('/sports/{sport}/basketball-score-sheet', [SportController::class, 'basketballScoreSheet'])->name('sports.basketball-score-sheet');
        Route::post('/sports/{sport}/basketball-score-sheet/download', [SportController::class, 'downloadBasketballScoreSheet'])->name('sports.basketball-score-sheet.download');
        Route::get('/sports/{sport}/volleyball-score-sheet', [SportController::class, 'volleyballScoreSheet'])->name('sports.volleyball-score-sheet');
        Route::post('/sports/{sport}/volleyball-score-sheet/download', [SportController::class, 'downloadVolleyballScoreSheet'])->name('sports.volleyball-score-sheet.download');
        Route::post('/sports/{sport}/bracket/reset', [SportController::class, 'resetBracket'])->name('sports.bracket.reset');
        Route::post('/sports/{sport}/bracket/{match}/result', [SportController::class, 'recordBracketResult'])->name('sports.bracket.result');
        Route::post('/sports/{sport}/bracket/{match}/schedule', [SportController::class, 'scheduleBracketMatch'])->name('sports.bracket.schedule');
        Route::get('/sports/{sport}/participants/assign', [SportController::class, 'assignParticipants'])->name('sports.participants.assign');
        Route::post('/sports/{sport}/participants/assign', [SportController::class, 'storeParticipants'])->name('sports.participants.store');
        Route::post('/sports/{sport}/participants/bulk-remove', [SportController::class, 'removeParticipants'])->name('sports.participants.bulk-remove');
        Route::get('/sports/{sport}/participants', [SportController::class, 'participants'])->name('sports.participants');
        Route::get('/sports/{sport}/events', [SportController::class, 'events'])->name('sports.events.index');
        Route::get('/sport-modules', fn () => redirect()->route('admin.sports.index'))->name('sport-modules.index');
        Route::get('/sport-modules/{sport}', [SportModuleController::class, 'show'])->name('sport-modules.show');
        Route::post('/sports/{sport}/restore', [SportController::class, 'restore'])->name('sports.restore');
        Route::resource('events', EventController::class)->except(['show', 'destroy']);
        Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
        Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
        Route::post('/registrations/{registration}/withdraw', [RegistrationController::class, 'withdraw'])->name('registrations.withdraw');
        Route::get('/participation-rules', [RegistrationController::class, 'rules'])->name('participation-rules.index');
        Route::post('/participation-rules', [RegistrationController::class, 'storeRule'])->name('participation-rules.store');
        Route::get('/competition', [CompetitionController::class, 'index'])->name('competition.index');
        Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
    });

    Route::prefix('coordinator')->as('coordinator.')->middleware('role:coordinator')->group(function () {
        Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
        Route::post('/requests', [CoordinatorDashboardController::class, 'storeRequest'])->name('requests.store');
    });
});
