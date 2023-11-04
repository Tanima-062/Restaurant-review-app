<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreSearchRequest extends FormRequest
{

    use ValidationFailTrait;

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
    public function rules(Request $request)
    {
        $suggestCd = $request['suggestCd'];
        $lowerPrice = $request['lowerPrice'];
        $upperPrice = $request['upperPrice'];

        // suggestCdに現在地(CURRENT_LOC)が選択されていた場合のバリデーション
        $validCurrentLoc = $suggestCd === config('code.suggestCd.current') ? 'numeric|required_with:suggestCd' : 'numeric|nullable';

        // suggestCdに駅(STATION) or エリア(AREA)が選択されていた場合のバリデーション
        if ($suggestCd === config('code.suggestCd.station') || $suggestCd === config('code.suggestCd.area')) {
            $validSuggestText = 'string|required_with:suggestCd';
        } else {
            $validSuggestText = 'string|nullable';
        }

        // lowerPriceとupperPriceのバリデーション
        $validLowerPrice = !is_null($upperPrice) ? 'integer|nullable|lt:upperPrice' : 'integer|nullable';
        $validUpperPrice = !is_null($lowerPrice) ? 'integer|nullable|gt:lowerPrice' : 'integer|nullable';

        return [
            'cookingGenreCd' => 'string|nullable',
            'menuGenreCd' => 'string|nullable',
            'suggestCd' => 'string|nullable',
            'suggestText' => $validSuggestText,
            'visitDate' => 'date_format:Y-m-d|nullable',
            'visitTime' => 'date_format:H:i|nullable',
            'visitPeople' => 'integer|nullable',
            'page' => 'integer|nullable',
            'latitude' => $validCurrentLoc,
            'longitude' => $validCurrentLoc,
            // TO RS以外はTORS両方検索になる
            'appCd' => 'string|nullable',
            'lowerPrice' => $validLowerPrice,
            'upperPrice' => $validUpperPrice,
            'zone' => 'integer|nullable|required_with:lowerPrice,upperPrice',
        ];
    }

    public function messages()
    {
        // suggestCdに現在地が選択されていた場合のバリデーションメッセージ
        $validMessage = 'suggestCdを'.config('code.suggestCd.current').'とした場合は、:attributeを必ず指定してください。';

        return [
            'latitude.required_with' => $validMessage,
            'longitude.required_with' => $validMessage,
        ];
    }
}
