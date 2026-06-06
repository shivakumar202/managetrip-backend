<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    protected function authorizePermission(Request $request, string $permission)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Refresh user with eager-loaded roles and permissions
        $user = $user->load(['roles.permissions']);
        
        if (!$user->hasPermission($permission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        return response()->json(User::with('roles')->get());
    }

    public function show(Request $request, $id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request)
    {
        if ($response = $this->authorizePermission($request, 'manage_users')) {
            return $response;
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles')) {
            $roleIds = Role::whereIn('name', (array) $request->roles)->orWhereIn('id', (array) $request->roles)->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_users')) {
            return $response;
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            $roleIds = Role::whereIn('name', (array) $request->roles)->orWhereIn('id', (array) $request->roles)->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return response()->json($user->load('roles'), 200);
    }

    public function destroy(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_users')) {
            return $response;
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted'], 200);
    }

    public function assignRoles(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_users')) {
            return $response;
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required'],
        ]);

        $roleIds = Role::whereIn('name', $request->roles)
            ->orWhereIn('id', $request->roles)
            ->pluck('id');

        $user->roles()->syncWithoutDetaching($roleIds);

        return response()->json($user->load('roles'), 200);
    }

    public function removeRole(Request $request, $id, $roleId)
    {
        if ($response = $this->authorizePermission($request, 'manage_users')) {
            return $response;
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $user->roles()->detach($role->id);

        return response()->json($user->load('roles'), 200);
    }
}
