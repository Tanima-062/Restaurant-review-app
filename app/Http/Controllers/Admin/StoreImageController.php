<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImageEditRequest;
use App\Libs\ImageUpload;
use App\Models\Image;
use App\Models\Store;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Storage;
use Validator;

class StoreImageController extends Controller
{
    public const STORE_IMAGE = 'gcs';
    public const STORE_IMAGE_PATH = 'store/';

    /**
     * 管理画面 - 店舗画像(編集画面).
     *
     * @param int id
     *
     * @return RedirectResponse|Redirector
     */
    public function editForm(int $id)
    {
        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeImageRedirectTo' => url()->previous()]);
        }

        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御
        $storeImages = Image::storeId($id)->orderBy('id', 'asc');

        return view(
            'admin.Store.Image.edit',
            [
                'store' => $store,
                'storeImageExists' => $storeImages->exists(),
                'storeImages' => $storeImages->get()->toArray(),
                'storeImageCodes' => config('const.storeImage.image_cd'),
            ]
        );
    }

    /**
     * 管理画面 - 店舗画像(編集).
     *
     * @param StoreImageEditRequest request
     * @param int id
     *
     * @return RedirectResponse|Redirector
     */
    public function edit(StoreImageEditRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $store = Store::find($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            $images = Image::storeId($id)->get();
            $stores = $request->input('storeImage');
            $uploadImage = $request->file('storeImage');
            $storeCode = $request->input('store_code');

            foreach ($stores as $key => $value) {
                if (!empty($uploadImage[$key])) {
                    $inputImage = $uploadImage[$key]['image_path'];
                    $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '').$storeCode.'/'.self::STORE_IMAGE_PATH;
                    $oldFileName = basename($images[$key]->url);

                    // 既存ファイル削除
                    if (!empty($images[$key]->url)) {
                        Storage::disk(self::STORE_IMAGE)->delete($dirPath.$oldFileName);
                    }

                    // 新ファイルアップロード
                    $fileName = basename($inputImage).'.'.$inputImage->extension();

                    // gcsへ画像保存
                    Storage::disk(self::STORE_IMAGE)
                    ->putFileAs($dirPath, $inputImage, $fileName);

                    Image::withoutGlobalScopes()
                    ->where('id', $value['id'])
                    ->where('store_id', $value['store_id'])
                    ->update([
                        'image_cd' => $value['image_cd'],
                        'url' => ImageUpload::environment().$dirPath.$fileName,
                        'weight' => isset($value['weight']) ? $value['weight'] : 0,
                    ]);
                }
                // 画像添付無しの更新
                Image::withoutGlobalScopes()
                ->where('id', $value['id'])
                ->where('store_id', $value['store_id'])
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
                sprintf('「%s」の画像設定を更新できませんでした。', $request->input('store_name'))
            );
        }

        return redirect(url()->previous())->with(
            'message',
            sprintf('「%s」の画像設定を更新しました。', $request->input('store_name'))
        );
    }

    /**
     * 管理画面 - 店舗画像(追加画面).
     *
     * @param int id
     *
     * @return RedirectResponse|Redirector
     */
    public function addForm(int $id)
    {
        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御
        $storeImages = Image::storeId($id)->orderBy('id', 'asc');

        return view(
            'admin.Store.Image.add',
            [
                'store' => $store,
                'storeImageExists' => $storeImages->exists(),
                'storeImages' => $storeImages->get()->toArray(),
                'storeAppCodes' => config('const.storeImage.app_cd'),
                'storeImageCodes' => config('const.storeImage.image_cd'),
            ]
        );
    }

    /**
     * 管理画面 - 店舗画像(追加).
     *
     * @param Request request
     *
     * @return RedirectResponse|Redirector
     *
     * @throws Exception
     */
    public function add(Request $request)
    {
        if ($request->ajax()) {
            $rules = [
                'storeImage.*.image_cd' => 'required',
                'storeImage.*.image_path' => 'required|mimes:png,jpg,jpeg|max:8192',
            ];
            $store = Store::find($request->input('store_id'));
            $this->authorize('store', $store); // ポリシーによる、個別に制御

            $stores = $request->input('storeImage');
            $uploadImage = $request->file('storeImage');
            $storeCode = $request->input('store_code');

            $error = Validator::make($request->all(), $rules);
            if ($error->fails()) {
                return response()->json([
                    'error' => $error->errors()->all(),
                ]);
            }

            try {
                \DB::beginTransaction();
                foreach ($stores as $key => $value) {
                    $inputImage = $uploadImage[$key]['image_path'];
                    $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '').$storeCode.'/'.self::STORE_IMAGE_PATH;

                    // 新ファイルアップロード
                    $fileName = basename($inputImage).'.'.$inputImage->extension();

                    // gcsへ画像保存
                    Storage::disk(self::STORE_IMAGE)
                        ->putFileAs($dirPath, $inputImage, $fileName);

                    Image::create([
                            'image_cd' => $value['image_cd'],
                            'url' => ImageUpload::environment().$dirPath.$fileName,
                            'store_id' => $request->input('store_id'),
                        ]);
                }
                \DB::commit();
            } catch (Exception $e) {
                report($e);
                \DB::rollback();

                return response()->json([
                    'error' => [sprintf('「%s」店舗画像を追加できませんでした。', $request->input('store_name'))],
                    'url' => route('admin.store.image.editForm', ['id' => $request->input('store_id')]),
                ]);
            }

            return response()->json([
                'success' => sprintf('「%s」店舗画像を追加しました。', $request->input('store_name')),
                'url' => route('admin.store.image.editForm', ['id' => $request->input('store_id')]),
            ]);
        }
    }

    /**
     * 管理画面 - 店舗画像(削除).
     *
     * @param int id
     * @param int image_id
     *
     * @return RedirectResponse|Redirector
     */
    public function delete(int $id, int $image_id)
    {
        try {
            $store = Store::with('image')->find($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            $storeCode = $store->code;
            $storeImage = Image::findOrFail($image_id);

            if (!empty($storeImage->url)) {
                Storage::disk(self::STORE_IMAGE)->delete($storeCode.'/'.self::STORE_IMAGE_PATH.basename($storeImage->url));
            }
            $storeImage->delete();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
