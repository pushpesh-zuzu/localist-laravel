<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\Html\Builder;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\{
    Auth,
    Hash,
    DB,
    Mail,
    Validator
};
use Illuminate\Validation\Rule;

class EmailSettingsController extends Controller
{
    public function index(Request $request, Builder $builder)
    {
        $data['settings'] = EmailSetting::get()->toArray();
        return view('email-settings.list', $data);
    }

    public function create()
    {
        $data['aRow'] = [];
        return view('email-settings.create', $data);
    }

    public function store(Request $request)
    {

        $this->validate($request);
        if (empty($request->setting_value)) {
            $data['setting_value'] = 1;
        }
        $data['setting_name'] = $request->setting_name;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = EmailSetting::insertGetId($data);
        return redirect()->route('email-settings.index')->with('success', 'Email Setting created successfully.');
    }

    public function changeSettingStatus(Request $request)
    {
        $this->validate($request, 'update');

        $column = $request->settingType === 'email' ? 'setting_value' : 'whatsapp_setting_value';

        EmailSetting::where('setting_name', $request->setting_name)
            ->update([$column => $request->setting_value]);
    }


    protected function validate(Request $request, $action = 'add')
    {
        $validator = null;
        if ($action == 'add') {
            $validator = \Validator::make($request->all(), [
                'setting_name' => 'required|unique:email_settings,setting_name'
            ], [
                'setting_name.required' => 'Setting name is required.'
            ]);
        } else {
            $validator = \Validator::make($request->all(), [
                'setting_name' => [
                    'required',
                    Rule::unique('email_settings')->where(function ($query) use ($request) {
                        return $query->where('setting_name', $request->setting_name);
                    })->ignore($request->id),
                ]
            ], [
                'setting_name.required' => 'Setting name is required.'
            ]);
        }


        $validator->validate();
    }
}
