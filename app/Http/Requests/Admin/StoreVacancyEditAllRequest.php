<?php

namespace App\Http\Requests\Admin;

use App\Models\OpeningHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreVacancyEditAllRequest extends FormRequest
{
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
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
        ];
    }

    public function attributes()
    {
        $return = [];
        $return['start'] = '開始日';
        $return['end'] = '終了日';

        return $return;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = request()->all();

            $start = new Carbon($data['start']);
            $end = new Carbon($data['end']);
            $now = new Carbon();

            // 開始日は明日以上３ヶ月後の月の最後の日以下
            $lastDayOf3MonthsAfter = $now->copy()->addMonths(3)->lastOfMonth();
            if (!($start->gte($now->copy()->tomorrow()) && $start->lte($lastDayOf3MonthsAfter))) {
                $validator->errors()->add('start', '開始日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。');
            }
            // 開始日は明日以上３ヶ月後の月の最後の日以下
            $lastDayOf3MonthsAfter = $now->copy()->addMonths(3)->lastOfMonth();
            if (!($end->gte($now->copy()->tomorrow()) && $end->lte($lastDayOf3MonthsAfter))) {
                $validator->errors()->add('end', '終了日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。');
            }

            // 曜日は１つ以上選択必須
            $isChecked = false;
            $data['week'] = isset($data['week']) ? $data['week'] : session('paramWeek');
            foreach ($data['week'] as $week) {
                if ($week === '1') {
                    $isChecked = true;
                }
            }
            if (!$isChecked) {
                $validator->errors()->add('week', '営業曜日は1つ以上必須です。');
            }

            //選択した曜日が全て同じopening_hoursのレコードで設定されているかどうか
            $regexp = ['[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]'];
            foreach ($data['week'] as $key => $w) {
                if ($w === '1') {
                    $regexp[$key] = '['.$w.']';
                }
            }
            $ops = OpeningHour::where('store_id', $this->route('id'))
            ->where('week', 'regexp', implode('', $regexp))
            ->get();

            if (count($ops) === 0) {
                $validator->errors()->add('week', '営業時間が異なる営業曜日は同時に登録できません。');
            }
        });
    }
}
