<?php

namespace App\Http\Response\Format;

use Illuminate\Support\Str;

/**
 * JSONのフォーマッティング.
 */
class ResponseFormatter
{
    private $exceptionalKeys = [
        'email_1',
    ];

    /**
     * Eloquent modelのフォーマット.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return string
     */
    public function formatEloquentModel($model)
    {
        $key = Str::camel(Str::singular($model->getTable()));

        $formattedModel = $this->camelCaseKeys($model->toArray());

        return $formattedModel;
    }

    /**
     * Eloquent collectionのフォーマット.
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     *
     * @return string
     */
    public function formatEloquentCollection($collection)
    {
        if (empty($collection)) {
            return $collection;
        }

        $model = $collection->first();
        $key = Str::camel(Str::plural($model->getTable()));

        $formattedCollection = $this->camelCaseKeys($collection->toArray());

        return $formattedCollection;
    }

    /**
     * Arrayableインターフェース実装した配列かインスタンスをフォーマット.
     *
     * @param array|\Illuminate\Contracts\Support\Arrayable $content
     *
     * @return string
     */
    public function formatArray($content)
    {
        $arr = [];
        foreach ($content as $key => $value) {
            if (preg_match('/_/', $key) && !$this->isExceptionalKey($key)) {
                $key = lcfirst(implode('', array_map('ucfirst', explode('_', $key))));
            }

            if (is_array($value)) {
                $value = $this->formatArray($value);
            }

            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * 配列のキーをキャメルケースへ変換.
     *
     * @return array
     */
    protected function camelCaseKeys(array $array)
    {
        $formatted = [];

        foreach ($array as $key => $value) {
            if ($this->isExceptionalKey($key)) {
                continue;
            }
            if (is_array($value)) {
                $formatted[Str::camel($key)] = $this->camelCaseKeys($value);
            } else {
                $formatted[Str::camel($key)] = $value;
            }
        }

        return $formatted;
    }

    private function isExceptionalKey($key)
    {
        return in_array($key, $this->exceptionalKeys);
    }
}
