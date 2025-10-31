<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;

class RoleController extends Controller
{


    public function index(Request $request)
    {

        // abort_if(!auth()->user()->can('role.view'), 403, __('User does not have the right permissions.'));

        $roles = DB::table('roles')->select('roles.id', 'roles.name')->orderBy('id', 'asc');

        if ($request->ajax()) {
            return DataTables::of($roles)

                ->addIndexColumn()
                ->editColumn('action', function ($user) {
                    $html = "";

                    if (!in_array($user->id, ['0'])) {
                        // if (auth()->user()->can('role.edit')) {
                            $html .= '<a href="' . route('roles.edit', $user->id) . '" title="Edit Detail"><i class="fa fa-edit"></i></a>  ';
                        // }
                    }

                    if (!in_array($user->id, ['1'])) {
                        if (auth()->user()->can('role.delete')) {
                            $html .= '| <form action="' . route('roles.destroy', $user->id) . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this role?\');">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="" title="Delete"><i class="fa fa-trash"></i></button>
                    </form>';
                        }
                    }

                    return $html;
                })
                ->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('roles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        abort_if(!auth()->user()->can('role.create'), 403, __('User does not have the right permissions.'));

        $allPermissions = Permission::select('id', 'name', 'heading', 'title')->get();

        $custom_permission = [];
        foreach ($allPermissions as $permission) {
            $heading = $permission->heading;
            $custom_permission[$heading][] = $permission;
        }

        return view('roles.create', compact('custom_permission'));
    }


    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('role.create'), 403, __('User does not have the right permissions.'));

        $request->validate([
            'name' => 'required|unique:roles,name'
        ], [
            'name.required' => __('Role name is required!'),
            'name.unique'   => __('Role name already taken!')
        ]);


        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {

            $permissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();

            $role->syncPermissions($permissions);
        }

        Session::flash('success', trans('Role has been created successfully'));
        return redirect()->route('roles.index');
    }



    public function edit($id)
    {
        // abort_if(!auth()->user()->can('role.edit'), 403, __('User does not have the right permissions.'));

        if (in_array($id, ['0'])) {
            Session::flash('success', trans('System role can not be edit'));
            return redirect(route('roles.index'));
        }

        $role = Role::with('permissions')->findOrFail($id);

        $role_permission = Permission::select('id', 'name', 'heading', 'title')->get();

        // Group by heading
        $custom_permission = [];
        foreach ($role_permission as $permission) {
            $heading = $permission->heading;
            $custom_permission[$heading][] = $permission;
        }

        return view('roles.edit', compact('role', 'custom_permission'));
    }


    public function update(Request $request, $id)
    {
        // abort_if(!auth()->user()->can('role.edit'), 403, __('User does not have the right permissions.'));

        if (in_array($id, ['0'])) {
            return redirect()->route('roles.index')->with('error', 'System role cannot be edit !');
        }

        $role = Role::find($id);

        $request->validate(
            [
                'name' => 'required|unique:roles,name,' . $id,
            ],
            [
                'name.required' => __('Role name is required !'),
                'name.unique'   => __('Role name already taken !'),
            ]
        );

        $permissions = $request->permissions ? (array) $request->permissions : [];
        $permissionNames = Permission::whereIn('id', $permissions)->pluck('name')->toArray();
        $role->name = $request->name;
        $role->save();

        $role->syncPermissions($permissionNames);

        return redirect()->route('roles.index')
            ->with('success', 'Roles has been updated Successfully');
    }



    public function destroy($id)
    {

        abort_if(!auth()->user()->can('role.delete'), 403, __('User does not have the right permissions.'));

        $role = Role::find($id);
        if (isset($role)) {
            $role->permissions()->detach();
            $role->delete();
            return redirect()->route('roles.index')
                ->with('success', 'Role has been deleted');
        } else {
            return redirect()->route('roles.index')
                ->with('error', 'Role not found');
        }
    }

    public function createPermission(Request $request)
    {

        Permission::create([
            'name' => $request->name,
        ]);

        echo __("Created");

        return back();
    }
}
