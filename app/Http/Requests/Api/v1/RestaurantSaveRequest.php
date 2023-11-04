<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantSaveRequest extends FormRequest
{
    use ValidationFailTrait;
    private $pattern = [
        'hostname' => '(?:[_\p{L}0-9][-_\p{L}0-9]*\.)*(?:[\p{L}0-9][-\p{L}0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})',
    ];

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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer.firstName' => 'string|required',
            'customer.lastName' => 'string|required',
            'customer.email' => 'string|required',
            'customer.tel' => 'string|required',
            'customer.request' => 'string|nullable',
            'application.persons' => 'integer|required',
            'application.menus.*.menu.id' => 'integer|required',
            'application.menus.*.menu.count' => 'integer|required|max:1',
            'application.menus.*.options.*.id' => 'integer|nullable',
            'application.menus.*.options.*.keywordId' => 'integer|nullable',
            'application.menus.*.options.*.contentsId' => 'integer|nullable',
            'application.visitDate' => 'date_format:Y-m-d|required',
            'application.visitTime' => 'date_format:H:i|required',
        ];
    }

    /**
     * バリデータインスタンスの設定.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['customer.email'])) {
                $value = $this->input('customer.email');

                // ドコモ、AU判定用ドメイン
                $CarrierDomain = '(docomo|ezweb)\.ne\.jp';
                if (is_string($value) && preg_match('/'.$CarrierDomain.'/i', $value)) {
                    // ドコモ、AUのみ独自ルール
                    $regex = '/^[\.a-z0-9!#$%&\'*+\/=?^_`{|}~-]+@'.$CarrierDomain.'$/i';
                } else {
                    $regex = '/^[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+)*@'
                    .$this->pattern['hostname'].'$/ui';
                }

                $return = (function (string $value, string $regex) {
                    if (is_string($regex) && is_scalar($value) && preg_match($regex, $value)) {
                        return true;
                    }

                    return false;
                })($value, $regex);
                $regs = [];
                if ($return === true && preg_match('/@('.$this->pattern['hostname'].')$/i', $value, $regs)) {
                    if (function_exists('getmxrr') && getmxrr($regs[1], $mxhosts)) {
                        return true;
                    }
                    if (function_exists('checkdnsrr') && checkdnsrr($regs[1], 'MX')) {
                        return true;
                    }
                    \Log::debug($value);

                    return is_array(gethostbynamel($regs[1]));
                }

                $validator->errors()->add('customer.email', '不正な形式のメールアドレスです。');
            }
        });
    }
}
