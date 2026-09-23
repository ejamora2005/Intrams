<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Sport; use Illuminate\View\View;
class SportModuleController extends Controller { public function index(): View { return view('admin.sport-modules.index',['sports'=>Sport::where('is_system',true)->orderByRaw("FIELD(code, 'BASKET-3X3', 'BASKET-5X5', 'VOLLEYBALL', 'BADMINTON', 'TABLE-TENNIS', 'BASEBALL', 'SOFTBALL', 'RUSSIAN-SOFTBALL')")->get()]); } public function show(Sport $sport): View { abort_unless($sport->is_system,404); return view('admin.sport-modules.show',['sport'=>$sport,'events'=>$sport->events()->with('edition')->latest('starts_at')->get()]); } }
