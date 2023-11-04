<?php

namespace App\Libs;

use App;

/**
 * 画像アップロード
 * Class ImageUpload
 * @package App\Lib
 */
class ImageUpload
{
    public static function environment()
    {
        if (App::environment('local')) {
            return 'https://jp.skyticket.jp/gourmet/';
        }
        if (App::environment('develop')) {
            return 'https://jp.skyticket.jp/gourmet/';
        }
        if (App::environment('staging')) {
            return 'https://skyticket.jp/gourmet/';
        }
        if (App::environment('production')) {
            return 'https://skyticket.jp/gourmet/';
        }
    }

    public static function store($image, $dirPath)
    {
        $fileName = basename($image) . '.' . $image->extension();

        \Storage::disk('gcs')
            ->putFileAs($dirPath, $image, $fileName);

        return $image;
    }
}
