<?php

namespace App\Providers;

use App\Http\Response\Format\ResponseFormatter;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(ResponseFactory $response, ResponseFormatter $responseFormatter)
    {
        $response->macro('toCamel', function ($data, $key = null) use ($responseFormatter) {
            $output = [];
            switch (true) {
                case $data instanceof \Illuminate\Database\Eloquent\Model:
                    $output = $responseFormatter->formatEloquentModel($data);
                break;

                case $data instanceof \Illuminate\Database\Eloquent\Collection:
                    $output = $responseFormatter->formatEloquentCollection($data);
                break;

                case is_array($data):
                case $data instanceof \Illuminate\Contracts\Support\Arrayable:
                    $output = $responseFormatter->formatArray($data);
                break;

                default:
                    $output = $data;
                break;
            }

            return (is_null($key)) ? $this->make($output) : $this->make([$key => $output]);
        });
    }
}
