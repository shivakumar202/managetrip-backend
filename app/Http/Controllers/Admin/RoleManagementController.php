<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleManagementController extends Controller
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
        return response()->json(Role::with('permissions')->get());
    }

    public function show(Request $request, $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json($role);
    }

    public function store(Request $request)
    {
        if ($response = $this->authorizePermission($request, 'manage_roles')) {
            return $response;
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $role = Role::create($request->only(['name', 'label', 'description']));

        if ($request->has('permissions')) {
            $permissionIds = Permission::whereIn('name', (array) $request->permissions)->orWhereIn('id', (array) $request->permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_roles')) {
            return $response;
        }

        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $role->fill($request->only(['name', 'label', 'description']));
        $role->save();

        if ($request->has('permissions')) {
            $permissionIds = Permission::whereIn('name', (array) $request->permissions)->orWhereIn('id', (array) $request->permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        return response()->json($role->load('permissions'), 200);
    }

    public function destroy(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_roles')) {
            return $response;
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted'], 200);
    }

    public function assignPermissions(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_roles')) {
            return $response;
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required'],
        ]);

        $permissionIds = Permission::whereIn('name', $request->permissions)
            ->orWhereIn('id', $request->permissions)
            ->pluck('id');

        $role->permissions()->syncWithoutDetaching($permissionIds);

        return response()->json($role->load('permissions'), 200);
    }

    public function removePermission(Request $request, $id, $permissionId)
    {
        if ($response = $this->authorizePermission($request, 'manage_roles')) {
            return $response;
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $role->permissions()->detach($permission->id);

        return response()->json($role->load('permissions'), 200);
    }
}
