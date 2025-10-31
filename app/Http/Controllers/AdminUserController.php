<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        abort_if(!auth()->user()->can('adminuser.view'), 403, __('User does not have the right permissions.'));
        $admins = Admin::with('role')->orderBy('id', 'asc')->get();
        return view('users.admin-user-list', compact('admins'));
    }

    public function create()
    {
        abort_if(!auth()->user()->can('adminuser.create'), 403, __('User does not have the right permissions.'));

        $roles = Role::pluck('name', 'id');
        $aRow = null;
        return view('users.create', compact('roles', 'aRow'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('adminuser.create'), 403, __('User does not have the right permissions.'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6',
            'role_id' => 'required|integer',
        ]);

        // Create admin and hash password
        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role using Spatie

        $admin->assignRole(Role::findById($validated['role_id']));

        return redirect()->route('admin-users.index')->with('success', 'Admin created successfully and role assigned.');
    }

    public function edit($id)
    {
        abort_if(!auth()->user()->can('adminuser.edit'), 403, __('User does not have the right permissions.'));

        $aRow = Admin::findOrFail($id);
        $roles = Role::pluck('name', 'id');
        return view('users.create', compact('roles', 'aRow'));
    }

    public function update(Request $request, $id)
    {
        abort_if(!auth()->user()->can('adminuser.edit'), 403, __('User does not have the right permissions.'));

        $admin = Admin::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('admins')->ignore($admin->id),
            ],
            'password' => 'nullable|min:6',
            'role_id' => 'required|integer',
        ]);


        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $admin->update($validated);

        if (!empty($validated['role_id'])) {
            $role = Role::findById($validated['role_id'], 'admin');
            $admin->syncRoles($role);
        }

        return redirect()->route('admin-users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        abort_if(!auth()->user()->can('adminuser.delete'), 403, __('User does not have the right permissions.'));

        if ($id == 1) {
            return redirect()->route('admin-users.index')->with('error', 'The main admin cannot be deleted.');
        }

        $admin = Admin::findOrFail($id);
        $admin->syncRoles([]);
        $admin->delete();

        return redirect()->route('admin-users.index')->with('success', 'User deleted successfully.');
    }
}
