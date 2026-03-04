<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'in:admin,user'],
            'subscription_status' => ['nullable', 'in:free,premium'],
            'sort' => ['nullable', 'in:newest,oldest,name,email'],
        ]);

        $filters = [
            'search' => $this->cleanString($filters['search'] ?? null),
            'role' => $this->cleanString($filters['role'] ?? null),
            'subscription_status' => $this->cleanString($filters['subscription_status'] ?? null),
            'sort' => $this->cleanString($filters['sort'] ?? null) ?: 'newest',
        ];

        $query = User::query()
            ->with('profile')
            ->when($filters['search'], function ($builder, string $search): void {
                $operator = $this->searchOperator();
                $term = '%'.$search.'%';

                $builder->where(function ($nested) use ($operator, $term): void {
                    $nested
                        ->where('name', $operator, $term)
                        ->orWhere('email', $operator, $term)
                        ->orWhere('phone', $operator, $term)
                        ->orWhereHas('profile', fn ($profile) => $profile->where('display_name', $operator, $term));
                });
            })
            ->when($filters['role'], fn ($builder, string $role) => $builder->where('role', $role))
            ->when($filters['subscription_status'], fn ($builder, string $status) => $builder->where('subscription_status', $status));

        match ($filters['sort']) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'email' => $query->orderBy('email'),
            default => $query->latest(),
        };

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'subscription_status' => $validated['subscription_status'],
            'subscription_expires_at' => $validated['subscription_expires_at'],
            'last_reset_at' => now(),
        ]);

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $validated['display_name'] ?: $validated['name']]
        );

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $user->load('profile');

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateRequest($request, $user);

        if ($request->user()->is($user) && $validated['role'] !== 'admin') {
            return back()->withInput()->with('error', 'You cannot remove your own admin role.');
        }

        if ($this->isLastAdminRoleChange($user, $validated['role'])) {
            return back()->withInput()->with('error', 'At least one admin account must remain.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'subscription_status' => $validated['subscription_status'],
            'subscription_expires_at' => $validated['subscription_expires_at'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $validated['display_name'] ?: $validated['name']]
        );

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->isLastAdminDeletion($user)) {
            return back()->with('error', 'Cannot delete the last admin account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    private function validateRequest(Request $request, ?User $user = null): array
    {
        $normalizedPhone = preg_replace('/\s+/', '', (string) $request->input('phone'));

        $request->merge([
            'email' => $this->cleanString((string) $request->input('email')),
            'phone' => $this->cleanString(is_string($normalizedPhone) ? $normalizedPhone : null),
            'display_name' => $this->cleanString((string) $request->input('display_name')),
        ]);

        $emailRule = Rule::unique('users', 'email');
        $phoneRule = Rule::unique('users', 'phone');
        if ($user) {
            $emailRule->ignore($user->id);
            $phoneRule->ignore($user->id);
        }

        $passwordRule = $user
            ? ['nullable', 'confirmed', Password::defaults()]
            : ['required', 'confirmed', Password::defaults()];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', $emailRule],
            'phone' => ['nullable', 'string', 'max:16', 'regex:/^\+256\d{9}$/', $phoneRule],
            'role' => ['required', 'in:admin,user'],
            'subscription_status' => ['required', 'in:free,premium'],
            'subscription_expires_at' => ['nullable', 'date'],
            'password' => $passwordRule,
        ], [
            'phone.regex' => 'Phone number must be in +256######### format.',
        ]);

        $validated['email'] = $this->cleanString($validated['email'] ?? null);
        $validated['phone'] = $this->cleanString($validated['phone'] ?? null);
        $validated['display_name'] = $this->cleanString($validated['display_name'] ?? null);

        if (($validated['subscription_status'] ?? 'free') !== 'premium') {
            $validated['subscription_expires_at'] = null;
        }

        if (empty($validated['password'])) {
            $validated['password'] = null;
        }

        return $validated;
    }

    private function isLastAdminRoleChange(User $user, string $newRole): bool
    {
        if ($user->role !== 'admin' || $newRole === 'admin') {
            return false;
        }

        return User::query()->where('role', 'admin')->count() <= 1;
    }

    private function isLastAdminDeletion(User $user): bool
    {
        if ($user->role !== 'admin') {
            return false;
        }

        return User::query()->where('role', 'admin')->count() <= 1;
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function searchOperator(): string
    {
        return User::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
