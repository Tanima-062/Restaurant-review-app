<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;

class StaffPasswordRequest extends FormRequest
{
    use StaffPasswordRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->samePassword()) {
                $validator->errors()->add('val-password2', '二回続けて同じパスワードは設定できません。');
            }
        });
    }

    /**
     * @return bool
     */
    private function samePassword()
    {
        $staff = Staff::find($this->id);
        if (!$staff) {
            return false;
        }

        $valPassword = $this->request->get('val-password2');

        if (Hash::check($valPassword, $staff->password)) {
            return true;
        }

        return false;
    }
}
