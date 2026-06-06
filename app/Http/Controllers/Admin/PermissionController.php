<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
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
        return response()->json(Permission::all());
    }

    public function store(Request $request)
    {
        if ($response = $this->authorizePermission($request, 'manage_permissions')) {
            return $response;
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission = Permission::create($request->only(['name', 'label', 'description']));

        return response()->json($permission, 201);
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_permissions')) {
            return $response;
        }

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission->fill($request->only(['name', 'label', 'description']));
        $permission->save();

        return response()->json($permission, 200);
    }

    public function destroy(Request $request, $id)
    {
        if ($response = $this->authorizePermission($request, 'manage_permissions')) {
            return $response;
        }

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $permission->delete();

        return response()->json(['message' => 'Permission deleted'], 200);
    }
}
