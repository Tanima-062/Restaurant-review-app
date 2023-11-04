<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoryAddRequest;
use App\Http\Requests\Admin\StoryEditRequest;
use App\Http\Requests\Admin\StorySearchRequest;
use App\Libs\ImageUpload;
use App\Models\Image;
use App\Models\Story;
use DB;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Storage;

class StoryController extends AdminController
{
    public const STORY_IMAGE = 'gcs';
    public const STORY_IMAGE_PATH = 'story/';

    /**
     * 管理画面 - ストーリーマスタ一覧
     *
     * @param StorySearchRequest $request
     * @return Factory|View
     */
    public function index(StorySearchRequest $request)
    {
        $stories = Story::with('image')
            ->adminSearchFilter($request->validated())->sortable()->paginate(30);

        return view('admin.Story.index', [
            'stories' => $stories,
            'app_cd' => config('code.appCd'),
        ]);
    }

    /**
     * 管理画面 - ストーリーマスタ(編集画面)
     *
     * @param int $id
     * @return Factory|View
     */
    public function editForm(int $id)
    {
        $story = Story::with('image')->find($id);

        return view('admin.Story.edit', [
            'story' => $story,
            'app_cd' => config('code.appCd'),
        ]);
    }

    /**
     * 管理画面 - ストーリーマスタ(更新)
     *
     * @param StoryEditRequest $request
     * @param int $id
     * @return RedirectResponse|Redirector
     */
    public function edit(StoryEditRequest $request, int $id)
    {
        $story = Story::with('image')->find($id);
        $uploadImage = $request->file('image');

        try {
            DB::beginTransaction();
            $story->update([
                'title' => $request->input('title'),
                'guide_url' => $request->input('url'),
                'published' => (! empty($request->input('published'))) ? 1 : 0,
                'app_cd' => $request->input('app_cd'),
            ]);

            // 画像ファイルの保存処理
            if ($uploadImage) {
                $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX','').self::STORY_IMAGE_PATH . $id . '/';
                $oldFileName = basename($story->image->url);

                // 既存ファイル削除
                if (! empty($story->image->url)) {
                    Storage::disk(self::STORY_IMAGE)->delete( $dirPath . $oldFileName);
                }
                // 新ファイルアップロード
                $fileName = basename($uploadImage) . '.' . $uploadImage->extension();

                // gcsへ画像保存
                ImageUpload::store($uploadImage, $dirPath);

                $story->image()->update([
                    'url' => ImageUpload::environment() . $dirPath . $fileName,
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            report($e);
            DB::rollback();

            return redirect(route('admin.story'))
            ->with('custom_error', sprintf('ストーリー「%s」を更新できませんでした', $request->input('title')));
        }

        return redirect(route('admin.story'))
            ->with('message', sprintf('ストーリー「%s」を更新しました', $request->input('title')));
    }

    /**
     * 管理画面 - ストーリーマスタ(追加画面)
     *
     * @return Factory|View
     */
    public function addForm()
    {
        return view('admin.Story.add', [
            'app_cd' => config('code.appCd'),
        ]);
    }

    /**
     * 管理画面 - ストーリーマスタ(追加)
     *
     * @param StoryAddRequest $request
     * @return RedirectResponse|Redirector
     */
    public function add(StoryAddRequest $request)
    {
        try {
            DB::beginTransaction();

            $uploadImage = $request->file('image');
            $storyId = Story::latest()->first();
            $lastId = $storyId ? ($storyId->id) + 1 : 1;

            $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '').self::STORY_IMAGE_PATH.$lastId.'/';
            $fileName = basename($uploadImage).'.'.$uploadImage->extension();

            // gcsへ画像保存
            ImageUpload::store($uploadImage, $dirPath);

            $image = Image::create(['url' => ImageUpload::environment() . $dirPath . $fileName]);

            $story = Story::firstOrCreate([
                'title' => $request->input('title'),
                'guide_url' => $request->input('url'),
                'published' => (! empty($request->input('published'))) ? 1 : 0,
                'image_id' => $image->id,
                'app_cd' => $request->input('app_cd'),
            ]);

            // 作成したstoryのIDと$lastIdが異なる場合、アップロードしたファイルを移動する（rollback処理により発番ID=lastIDにならない可能性があるため）
            if ($story->id != $lastId) {
                // $lastIdフォルダからstoryIdフォルダに移動する
                $newDirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '') . self::STORY_IMAGE_PATH . $story->id . '/';
                \Storage::disk(self::STORY_IMAGE)->move($dirPath . $fileName, $newDirPath . $fileName);

                // imageテーブルの情報も更新する
                Image::find($image->id)->update(['url' => ImageUpload::environment() . $newDirPath . $fileName]);
            }

            DB::commit();
        } catch (\Throwable $e){
            report($e);
            DB::rollback();

            return redirect(route('admin.story'))
            ->with('custom_error', sprintf('ストーリー「%s」を追加できませんでした', $request->input('title')));
        }

        return redirect(route('admin.story'))
            ->with('message', sprintf("ストーリー「%s」を追加しました", $request->input('title')));
    }

    /**
     * 管理画面 - ストーリーマスタ(削除)
     *
     * @param int $id
     * @return RedirectResponse|Redirector
     */
    public function delete(int $id)
    {
        try {
            DB::beginTransaction();
            $story = Story::with('image')->findOrFail($id);
            $story->delete();

            if (! empty($story->image->url)) {
                $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '') . self::STORY_IMAGE_PATH . $story->id . '/';
                Storage::disk(self::STORY_IMAGE)->delete($dirPath. basename($story->image->url));
            }

            $story->image()->delete();
            DB::commit();

            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            report($e);
            DB::rollBack();

            return false;
        }
    }
}
