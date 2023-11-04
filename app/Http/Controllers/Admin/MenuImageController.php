<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuImageAddRequest;
use App\Http\Requests\Admin\MenuImageEditRequest;
use App\Libs\ImageUpload;
use App\Models\Image;
use App\Models\Menu;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Storage;

class MenuImageController extends Controller
{
    public const MENU_IMAGE = 'gcs';
    public const MENU_IMAGE_PATH = 'menu/';

    /**
     * 管理画面 - メニュー画像(編集画面)
     *
     * @param int $id
     * @return RedirectResponse|Redirector
     */
    public function editForm($id)
    {
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['menuImageRedirectTo' => url()->previous()]);
        }

        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
        $menuImages = Image::menuId($id)->orderBy('id', 'asc');
        $imageCd = array_keys(config('const.menuImage.image_cd'));

        return view(
            'admin.Menu.Image.edit',
            [
                'menu' => $menu,
                'menuImageExists' => $menuImages->exists(),
                'menuImages' => $menuImages->whereIn('image_cd', $imageCd)->get()->toArray(),
                'menuAppCodes' => config('const.menuImage.app_cd'),
                'menuImageCodes' => config('const.menuImage.image_cd'),
            ]
        );
    }

    /**
     * 管理画面 - メニュー画像(編集)
     *
     * @param MenuImageEditRequest $request
     * @param int $id
     * @return RedirectResponse|Redirector
     */
    public function edit(MenuImageEditRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $image = Image::menuId($id)->get();
            $menus = $request->input('menu');
            $uploadImage = $request->file('menu');

            $getStoreCode = Menu::query()
            ->where('id', $request->menu_id)
            ->with('store')->first();
            $storeCode = optional($getStoreCode->store)->code;

            foreach ($menus as $key => $value) {
                if (!empty($uploadImage[$key])) {
                    $inputImage = $uploadImage[$key]['image_path'];
                    $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '').$storeCode.'/'.self::MENU_IMAGE_PATH;
                    $oldFileName = basename($image[$key]->url);

                    // 既存ファイル削除
                    if (!empty($image[$key]->url)) {
                        Storage::disk(self::MENU_IMAGE)->delete($dirPath.$oldFileName);
                    }
                    // 新ファイルアップロード
                    $fileName = basename($inputImage).'.'.$inputImage->extension();

                    // gcsへ画像保存
                    Storage::disk(self::MENU_IMAGE)
                    ->putFileAs($dirPath, $inputImage, $fileName);

                    Image::withoutGlobalScopes()
                    ->where('id', $value['id'])
                    ->where('menu_id', $value['menu_id'])
                    ->update([
                        'image_cd' => $value['image_cd'],
                        'url' => ImageUpload::environment() . $dirPath . $fileName,
                        'weight' => isset($value['weight']) ? $value['weight'] : 0,
                    ]);
                }
                // 画像添付無しの更新
                Image::withoutGlobalScopes()
                ->where('id', $value['id'])
                ->where('menu_id', $value['menu_id'])
                ->update([
                    'image_cd' => $value['image_cd'],
                    'weight' => isset($value['weight']) ? $value['weight'] : 0,
                ]);
            }
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(url()->previous())->with(
                'custom_error',
                sprintf('「%s」の画像設定を更新できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(url()->previous())->with(
            'message',
            sprintf('「%s」の画像設定を更新しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニュー画像(追加画面)
     *
     * @param int $id
     * @return RedirectResponse|Redirector
     */
    public function addForm(int $id)
    {
        $menuImages = Image::menuId($id)->orderBy('id', 'asc');

        return view(
            'admin.Menu.Image.add',
            [
                'menu' => Menu::find($id),
                'menuImageExists' => $menuImages->exists(),
                'menuImages' => $menuImages->get()->toArray(),
                'menuAppCodes' => config('const.menuImage.app_cd'),
                'menuImageCodes' => config('const.menuImage.image_cd')
            ]
        );
    }

    /**
     * 管理画面 - メニュー画像(追加)
     *
     * @param MenuImageAddRequest $request
     * @return RedirectResponse|Redirector
     * @throws Exception
     */
    public function add(MenuImageAddRequest $request)
    {
        try {
            \DB::beginTransaction();
            $uploadImage = $request->file('image_path');
            $getStoreCode = Menu::query()
            ->where('id', $request->input('menu_id'))
            ->with('store')->first();
            $storeCode = optional($getStoreCode->store)->code;

            $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX','').$storeCode . '/' . self::MENU_IMAGE_PATH;
            $fileName = basename($uploadImage) . '.' . $uploadImage->extension();

            // gcsへ画像保存
            Storage::disk(self::MENU_IMAGE)
            ->putFileAs($dirPath, $uploadImage, $fileName);

            Image::create([
            'image_cd' => $request->input('image_cd'),
            'url' => ImageUpload::environment() . $dirPath . $fileName,
            'menu_id' => $request->input('menu_id')
        ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.menu.image.editForm', ['id' => $request->input('menu_id')]))->with(
                'custom_error',
                sprintf('「%s」の画像を追加できませんでした。', $request->input('menu_name'))
            );
        }

        return redirect(route('admin.menu.image.editForm', ['id' => $request->input('menu_id')]))->with(
            'message',
            sprintf('「%s」の画像を追加しました。', $request->input('menu_name'))
        );
    }

    /**
     * 管理画面 - メニュー画像(削除)
     *
     * @param int $id
     * @param int $image_id
     * @return RedirectResponse|Redirector
     */
    public function delete(int $id, int $image_id)
    {
        try {
            $menuImage = Image::findOrFail($image_id);
            $menuImage->delete();

            $getStoreCode = Menu::query()
                ->where('id', $menuImage->menu_id)
                ->with('store')->first();
            $storeCode = optional($getStoreCode->store)->code;

            if (! empty($menuImage->url)) {
                $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '').$storeCode . '/' . self::MENU_IMAGE_PATH;
                Storage::disk(self::MENU_IMAGE)->delete($dirPath . basename($menuImage->url));
            }

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
