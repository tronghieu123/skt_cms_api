<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\Cms\Admin;
use App\Models\Cms\Admin\Admin_Group;
use App\Models\Cms\Gateway\Gateway;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);
            $credentials = request(['username', 'password']);
            if (!Auth::attempt($credentials)) {
                return error_bad_request('Thông tin đăng nhập không đúng');
            }

            $admin = Admin::where('username', $request->username)->first();
            if (!Hash::check($request->password, $admin->password, [])) {
                return error_bad_request('Thông tin đăng nhập không đúng');
            }

            if(isset($admin->access_token) && $admin->access_token!="") {
                return response_custom($admin);
            } else {
                $tokenResult = $admin->createToken('authToken')->plainTextToken;
                Admin::where('_id', $admin['_id'])->update(['access_token' => $tokenResult]);
                $admin = Admin::where('username', $request->username)->first();
                $admin->login_at = parseTimestamp($admin->login_at);
                return response_custom($admin);
            }

        } catch (\Exception $error) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error in Login',
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        if($request->user()) {
            $ok = Admin::where('_id', $request->user()->_id)->update(['access_token' => '']);
            if($ok) {
                $request->user()->currentAccessToken()->delete();
                return response_custom('Successfully logged out');
            }
        }
        return error_bad_request();
    }


    public function role_group(Request $request) {
        $data = [
            'title' => 'Quyền CTV',
            'data' => [
                [
                    'menu_id'    => '65bc595a0a690863b70807e7',
                    'gw_id'      => '',
                    'api_access' => [],
                ],
                [
                    'menu_id'    => '65bc597a0a690863b70807eb',
                    'gw_id'      => '',
                    'api_access' => [],
                ],
                [
                    'menu_id'    => '65bc599b0a690863b70807ef',
                    'gw_id'      => '65bc5b830a690863b7080856',
                    'api_access' => ['manage'],
                ],
                [
                    'menu_id'    => '65bc59b10a690863b70807f3',
                    'gw_id'      => '65bc5b5c0a690863b708084b',
                    'api_access' => ['manage'],
                ]
            ],
            'is_show'    => 1,
            'created_at' => mongo_time(),
            'updated_at' => mongo_time()
        ];
        foreach ($data['data'] as $key => $value) {
            $gw = Gateway::where('menu_id', '=', $value['menu_id'])->first();
            if(!empty($gw)) {
                $data['data'][$key]['gw_id'] = $gw->_id;
            }
        }
        Admin_Group::insert($data);
        dd($data);
    }
}
