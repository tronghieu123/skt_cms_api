<?php

namespace App\Models\Sky\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UserRequest extends FormRequest
{
    protected $connection = 'sky_user';
    protected $table = 'user';

    public function rules()
    {
        $item = !empty($this->user) ? $this->user : '';
        $this->request->add(json_decode($this->data, true));
        if(!$item){
            return [
                'password' => [
                    'min:6',
                    // 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*]).{8,72}$/i',
                ],
                'username' => [
                    Rule::unique('user', 'username')
                        ->where(function ($query) {
                            return $query->where(function ($q) {
                                return $q->where('username', $this->username)->orWhere('email', $this->username)->orWhere('phone', $this->username);
                            });
                        }),
                    'required',
                ],
                'phone' => [
                    Rule::unique('user', 'username')
                        ->where(function ($query) {
                            return $query->where(function ($q) {
                                return $q->where('username', $this->username)->orWhere('email', $this->username)->orWhere('phone', $this->username);
                            });
                        }),
                    'required',
                ],
                'email' => [
                    Rule::unique('user', 'email')
                        ->where(function ($query) {
                            return $query->where(function ($q) {
                                return $q->where('username', $this->email)->orWhere('email', $this->email);
                            });
                        }),
                    'regex:/^[a-z][a-z0-9_\.]{2,32}@[a-z0-9]{2,}(\.[a-z0-9]{2,4}){1,2}$/i',
                ]
            ];
        }else{
            return [
                'password' => [
                    'min:8',
                    // 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*]).{8,72}$/i',
                ],
                'username' => [
                    Rule::unique('user', 'username')
                        ->where(function ($query) {
                            return $query->where(function ($q) {
                                return $q->where('username', $this->email)->orWhere('email', $this->email)->orWhere('phone', $this->email);
                            })->where('_id', '!=', $this->user['id']);
                        }),
                    'required',
                    'phone'
                ],
                'email' => [
                    Rule::unique('user', 'email')
                        ->where(function ($query) {
                            return $query->where(function ($q) {
                                return $q->where('username', $this->email)->orWhere('email', $this->email);
                            })->where('_id', '!=', $this->user['id']);
                        }),
                    'regex:/^[a-z][a-z0-9_\.]{2,32}@[a-z0-9]{2,}(\.[a-z0-9]{2,4}){1,2}$/i',
                ]
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code'      => Response::HTTP_BAD_REQUEST,
            'message'   => __('an_error_occurred'),
            'data'      => $validator->errors()
        ]));
    }

    public function messages()
    {
        return [
            //username
            'username.required' => 'Không được để trống Tên đăng nhập',
            'username.unique'   => 'Tên đăng nhập đã có người sử dụng',
            'username.max'      => 'Tên đăng nhập không hợp lệ',
            'username.regex'    => __('name_string'),
            //password
            'password.required' => __('password_required'),
            'password.regex'    => __('password_format'),
            'password.min'      => __('password_min'),
            'password.max'      => __('password_max'),
            //phone
            'phone.required'    => __('phone_required'),
            'phone.unique'      => __('phone_unique'),
            'phone.regex'       => __('phone_format'),
            //email
            'email.unique'      => __('email_unique'),
            'email.regex'       => __('email_format'),
            'address.required' => 'Không được để trống'
        ];
    }
}
