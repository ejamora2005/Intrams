<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OperationsAccountController extends Controller
{
    /** @var array<string, string> */
    private array $roles = [
        'gam' => 'General Athletics Manager',
        'tabulator' => 'Tabulator',
    ];

    public function index(Request $request): View
    {
        $search = $request->string('search')->value();
        $role = $request->string('role')->value();
        $status = $request->string('status')->value() ?: 'active';

        $accounts = User::query()
            ->with('managedTeam')
            ->whereIn('role', array_keys($this->roles))
            ->when(array_key_exists($role, $this->roles), fn ($query) => $query->where('role', $role))
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations-accounts.index', [
            'accounts' => $accounts,
            'roles' => $this->roles,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.operations-accounts.create', [
            'account' => new User(),
            'roles' => $this->roles,
            'teams' => $this->assignableTeams(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedAccountData($request);

        $account = User::query()->create([
            ...Arr::except($data, ['password']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.operations-accounts.edit', $account)->with('success', 'Operations account created.');
    }

    public function edit(User $operationsAccount): View
    {
        $this->ensureOperationsAccount($operationsAccount);

        return view('admin.operations-accounts.edit', [
            'account' => $operationsAccount,
            'roles' => $this->roles,
            'teams' => $this->assignableTeams($operationsAccount),
        ]);
    }

    public function update(Request $request, User $operationsAccount): RedirectResponse
    {
        $this->ensureOperationsAccount($operationsAccount);
        $data = $this->validatedAccountData($request, $operationsAccount);
        $changes = Arr::except($data, ['password']);

        if (! empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
        }

        $operationsAccount->update($changes);

        return back()->with('success', 'Operations account updated.');
    }

    /** @return array<string, mixed> */
    private function validatedAccountData(Request $request, ?User $account = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($account !== null) {
            $emailRule->ignore($account);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$account === null ? 'required' : 'nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(array_keys($this->roles))],
            'status' => ['required', 'in:active,inactive,suspended'],
            'managed_team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
        ]);

        if (! in_array($data['role'], ['gam', 'tabulator'], true)) {
            $data['managed_team_id'] = null;
        }

        return $data;
    }

    private function ensureOperationsAccount(User $account): void
    {
        abort_unless(array_key_exists($account->role, $this->roles), 404);
    }

    private function assignableTeams(?User $account = null)
    {
        $teams = Team::query()
            ->with('edition')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereHas('edition', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get();

        if ($account?->managed_team_id && ! $teams->contains('id', $account->managed_team_id)) {
            $account->loadMissing('managedTeam.edition');
            if ($account->managedTeam) {
                $teams->push($account->managedTeam);
            }
        }

        return $teams;
    }
}
