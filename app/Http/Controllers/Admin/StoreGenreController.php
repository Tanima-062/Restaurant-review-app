<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCookingGenreAddRequest;
use App\Http\Requests\Admin\StoreCookingGenreEditRequest;
use App\Http\Requests\Admin\StoreGenreRequestAdd;
use App\Http\Requests\Admin\StoreGenreRequestEdit;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Store;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class StoreGenreController extends Controller
{
    private $DETAILED;
    private $COOKING;

    public function __construct()
    {
        $this->DETAILED = config('const.genre.bigGenre.b-detailed.key');
        $this->COOKING = config('const.genre.bigGenre.b-cooking.key');
    }

    /**
     * 管理画面 - 店舗こだわりジャンル(追加画面)　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param int $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function addForm(int $id)
    {
        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御
        $middleGenres = Genre::getListByPath('', $this->DETAILED, 2)->get();

        return view('admin.Store.Genre.detailed_add', [
            'store' => $store,
            'middleGenres' => $middleGenres,
            'bigGenre' => $this->DETAILED,
        ]);
    }

    /**
     * 管理画面 - 店舗こだわりジャンル(追加)　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     * 
     * @param StoreGenreRequestAdd $request
     * @param int $id
     * @return Application|RedirectResponse|Redirector
     */
    public function add(StoreGenreRequestAdd $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $middleGenre = $request->input('middle_genre');
            $smallGenre = $request->input('small_genre');

            $path = sprintf('/%s/%s', $this->DETAILED, $middleGenre);
            $genres = Genre::getGenreMenu($path, '', $smallGenre)->get();

            if ($genres->count() == 0) {
                return redirect(route('admin.store.genre.add', ['id' => $id]))
                ->with('custom_error', sprintf("ジャンルが存在しませんでした"));
            }

            $genre = $genres->pop();
            $genreGroup = GenreGroup::firstOrNew([
            'genre_id' => $genre->id,
            'store_id' => $id
        ]);

            if (!empty($genreGroup->id)) {
                return redirect(route('admin.store.genre.add', ['id' => $id]))
                ->with('custom_error', sprintf('既に登録済みです'));
            }

            $genreGroup->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('custom_error', sprintf('ジャンルを追加できませんでした'));
        }

        return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('message', sprintf("ジャンルを追加しました"));
    }

    /**
     * 管理画面 - 店舗こだわり・料理ジャンル(編集/一覧画面)
     *
     * @param int $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function editForm(int $id)
    {
        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeGenreRedirectTo' => url()->previous()]);
        }

        $store = Store::find($id);
        $this->authorize('store', $store); // ポリシーによる、個別に制御
        $storeGroups = GenreGroup::where('store_id', $store->id)->get();
        $detailedGenreGroups = collect();
        $detailedMiddleGenres = Genre::getListByPath('', $this->DETAILED, 2)->get();
        $cookingGenreGroups = collect();
        $cookingMiddleGenres = Genre::getListByPath('', $this->COOKING, 2)->get();

        foreach ($storeGroups as $group) {
            $genre = Genre::find($group->genre_id);
            if (!$genre) {
                continue;
            }

            $paths = explode('/', $genre->path);
            $this->getGenre($detailedGenreGroups, $cookingGenreGroups, $genre, $group, $paths);
        }

        return view('admin.Store.Genre.edit', [
            'storeName' => $store->name,
            'bigGenre' => $this->DETAILED,
            'cookingGenre' => $this->COOKING,
            'detailedMiddleGenres' => $detailedMiddleGenres,
            'cookingMiddleGenres' => $cookingMiddleGenres,
            'detailedGenreGroups' => $detailedGenreGroups,
            'cookingGenreGroups' => $cookingGenreGroups,
            'id' => $id,
        ]);
    }

    /**
     * 管理画面 - 店舗こだわりジャンル(更新)
     *
     * @param StoreGenreRequestEdit $request
     * @param int $id
     * @return Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    public function edit(StoreGenreRequestEdit $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $store = Store::findOrFail($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            $genreGroupId = $request->input('genre_group_id');
            $middleGenre = $request->input('middle_genre');
            $smallGenre = $request->input('small_genre');

            if ($this->checkSimilar($smallGenre)) {
                return redirect(route('admin.store.genre.edit', ['id' => $id]))
                ->with('custom_error', sprintf("こだわりジャンルは重複して登録することはできません"));
            }

            $path = [];
            $count = count($middleGenre);
            for ($i = 0; $i < $count; $i++) {
                $path[$i] = sprintf("/%s/%s", $this->DETAILED, $middleGenre[$i]);
                $genres = Genre::getGenreMenu($path[$i], '', $smallGenre[$i])->get();

                if ($genres->count() === 0) {
                    \DB::rollBack();
                    return redirect(route('admin.store.genre.edit', ['id' => $id]))
                    ->with('custom_error', sprintf("こだわりジャンルが存在しませんでした"));
                }

                $genre = $genres->pop();
                $genreGroup = GenreGroup::find($genreGroupId[$i]);
                if (!$genreGroup) {
                    // 新規追加
                    $genreGroup = GenreGroup::firstOrNew([
                        'genre_id' => $genre->id,
                        'store_id' => $id
                    ]);
                    if (!empty($genreGroup->id)) {
                        \DB::rollBack();
                        return redirect(route('admin.store.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf('こだわりジャンルを保存できませんでした'));
                    }
                } else {
                    // 更新
                    $genreGroup->genre_id = $genre->id;
                }
                $genreGroup->save();
            }
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('custom_error', sprintf('こだわりジャンルを保存できませんでした'));
        }

        return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('message', sprintf("こだわりジャンルを保存しました"));
    }

    /**
     * 管理画面 - 店舗こだわり・料理ジャンル(削除)
     *
     * @param int $id
     * @return false|JsonResponse
     */
    public function delete(int $id)
    {
        try {
            $genreGroup = GenreGroup::find($id);
            $store = Store::find($genreGroup->store_id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            GenreGroup::destroy($id);
            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 管理画面 - 店舗料理ジャンル(更新)
     *
     * @param StoreCookingGenreEditRequest $request
     * @param int $id
     * @return Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    public function cookingEdit(StoreCookingGenreEditRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $appCd = $request->input('app_cd', '');
            $store = Store::findOrFail($id);
            $this->authorize('store', $store); // ポリシーによる、個別に制御
            $genreGroupId = $request->input('cooking_genre_group_id');
            $middleGenre = $request->input('cooking_middle_genre');
            $smallGenre = $request->input('cooking_small_genre');
            $small2Genre = $request->input('cooking_small2_genre', '');
            $delegate = $request->input('cooking_delegate');

            $path = [];
            $count = count($middleGenre);

            if (!isset(array_count_values($delegate)['main'])) {
                return redirect(route('admin.store.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf("メインジャンルを選択してください"));
            } elseif (array_count_values($delegate)['main'] > 1) {
                return redirect(route('admin.store.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf("メインジャンルは2つ以上設定できません"));
            }

            for ($i = 0; $i < $count; ++$i) {
                $genreGroup = GenreGroup::find($genreGroupId[$i]);
                $isDelegate = 0;
                if (is_null($small2Genre[$i])) {
                    $path[$i] = sprintf("/%s/%s", $this->COOKING, $middleGenre[$i]);
                    $genres = Genre::getGenreMenu($path[$i], $appCd, $smallGenre[$i])->get();
                } else {
                    $path[$i] = sprintf("/%s/%s/%s", $this->COOKING, $middleGenre[$i], $smallGenre[$i]);
                    $genres = Genre::getGenreMenu($path[$i], $appCd, $small2Genre[$i])->get();
                }

                if ($delegate[$i] === 'main') {
                    if (isset($middleGenre[$i])
                        && isset($smallGenre[$i])
                        && !isset($small2Genre[$i])) {
                        $isDelegate = 1;
                    } else {
                        \DB::rollBack();
                        return redirect(route('admin.store.genre.edit', ['id' => $id]))
                            ->with('custom_error', sprintf("小ジャンル2が設定されている場合はメインジャンルに設定できません"));
                    }
                } else {
                    $isDelegate = 0;
                }

                if ($genres->count() === 0) {
                    \DB::rollBack();
                    return redirect(route('admin.store.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf("料理ジャンルが存在しませんでした"));
                }

                $genre = $genres->pop();
                // 重複ジャンルバリデーション
                $arrayGenreGroupId = [];
                // Not検索をするために、配列化
                $arrayGenreGroupId[] = $genreGroupId[$i];
                $validGenre = GenreGroup::where('genre_id', $genre->id)
                                ->where('store_id', $store->id)
                                ->whereNotIn('id', $arrayGenreGroupId)
                                ->get();
                if (count($validGenre) !== 0) {
                    \DB::rollBack();
                    return redirect(route('admin.store.genre.edit', ['id' => $id]))
                    ->with('custom_error', sprintf("料理ジャンルは重複して登録することはできません。"));
                }

                if (!$genreGroup) {
                    // 新規追加
                    $genreGroup = GenreGroup::firstOrNew([
                        'genre_id' => $genre->id,
                        'store_id' => $id,
                        'is_delegate' => $isDelegate,
                    ]);
                    if (!empty($genreGroup->id)) {
                        \DB::rollBack();
                        return redirect(route('admin.store.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf('料理ジャンルを保存できませんでした'));
                    }

                } else {
                    // 更新
                    $genreGroup->is_delegate = $isDelegate;
                    $genreGroup->genre_id = $genre->id;
                }
                $genreGroup->save();
            }
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('custom_error', sprintf('料理ジャンルを保存できませんでした'));
        }

        return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('message', sprintf("料理ジャンルを保存しました"));
    }

    /**
     * 管理画面 - 店舗料理ジャンル(追加画面)　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function cookingAddForm(int $id)
    {
        $store = Store::find($id);
        $middleGenres = Genre::getListByPath('', $this->COOKING, 2)->get();

        return view('admin.Store.Genre.cooking_add', [
            'store' => $store,
            'middleGenres' => $middleGenres,
            'bigGenre' => $this->COOKING,
        ]);
    }

    /**
     * 管理画面 - 店舗料理ジャンル(追加) （※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param StoreCookingGenreAddRequest $request
     * @param int $id
     * @return Application|RedirectResponse|Redirector
     */
    public function cookingAdd(StoreCookingGenreAddRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $appCd = $request->input('app_cd', '');
            $middleGenre = $request->input('middle_genre');
            $smallGenre = $request->input('small_genre');
            $small2Genre = $request->input('small2_genre', '');
            $isDelegate = $request->input('is_delegate');

            if (empty($small2Genre)) {
                $path = sprintf('/%s/%s', $this->COOKING, $middleGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $smallGenre)->get();
            } else {
                $path = sprintf('/%s/%s/%s', $this->COOKING, $middleGenre, $smallGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $small2Genre)->get();
            }

            if ($genres->count() === 0) {
                return redirect(route('admin.store.genre.cooking.add', ['id' => $id]))
                ->with('custom_error', sprintf("料理ジャンルが存在しませんでした"));
            }

            $genre = $genres->pop();

            $genreGroup = GenreGroup::firstOrNew([
            'genre_id' => $genre->id,
            'store_id' => $id,
            'is_delegate' => $isDelegate,
        ]);

            if (!empty($genreGroup->id)) {
                return redirect(route('admin.store.genre.cooking.add', ['id' => $id]))
                ->with('custom_error', sprintf("既に登録済みです"));
            }

            $genreGroup->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('custom_error', sprintf('料理ジャンルを追加できませんでした'));
        }

        return redirect(route('admin.store.genre.edit', ['id' => $id]))
            ->with('message', sprintf('料理ジャンルを追加しました'));
    }

    /**
     * 管理画面 - 店舗こだわり・料理ジャンル取得用
     *
     * @param $detailedGenreGroups
     * @param $cookingGenreGroups
     * @param $genre
     * @param $group
     * @param array $paths
     * @return object
     */
    private function getGenre($detailedGenreGroups, $cookingGenreGroups, $genre, $group, array $paths): object
    {
        $smallGenres = collect();
        $small2Genres = collect();
        $middleGenre = $paths[2] ?? '';
        $smallGenre = $paths[3] ?? '';
        $detailed = ($paths[1] === strtolower($this->DETAILED));

        if (!empty($middleGenre)) {
            $smallGenres = Genre::getListByPath('', $middleGenre, 3)->get();
        }

        if (!empty($smallGenre)) {
            $small2Genres = Genre::getListByPath('', $smallGenre, 4)->get();
        } else {
            $smallGenre = $genre->genre_cd;
        }

        if ($detailed) {
            $detailedGenreGroups->push([
                'genreGroupId' => $group->id,
                'genre' => $genre,
                'middleGenre' => $middleGenre,
                'smallGenre' => $smallGenre,
                'smallGenres' => $smallGenres,
            ]);

            return $detailedGenreGroups;
        }

        $cookingGenreGroups->push([
            'genreGroupId' => $group->id,
            'delegate' => $group->is_delegate,
            'genre' => $genre,
            'middleGenre' => $middleGenre,
            'smallGenre' => $smallGenre,
            'smallGenres' => $smallGenres,
            'small2Genres' => $small2Genres
        ]);

        return $cookingGenreGroups;
    }

    /**
     * 配列の値の重複をチェック
     *
     * @param Array $list
     * 
     * @return Boolean
     */
    function checkSimilar(array $list)
    {
        $value_count = array_count_values($list); // 各値の出現回数を数える
        $max = max($value_count); // 最大の出現回数を取得する
        // 配列の値の重複なし
        if ($max == 1) {
            return false;
        // 配列の値の重複あり
        } else {
            return true;
        }
    }
}
