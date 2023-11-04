<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuGenreAddRequest;
use App\Http\Requests\Admin\MenuGenreEditRequest;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Menu;

class MenuGenreController extends Controller
{
    private $COOKING;

    public function __construct()
    {
        $this->COOKING = config('const.genre.bigGenre.b-cooking.key');
    }

    public function editForm(int $id)
    {
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['menuGenreRedirectTo' => url()->previous()]);
        }

        $menu = Menu::find($id);
        $this->authorize('menu', $menu); //  ポリシーによる、個別に制御
        $menuGroups = GenreGroup::where('menu_id', $menu->id)->get();

        $middleGenres = Genre::getListByPath($menu->app_cd, $this->COOKING, 2)->get();
        $genreGroups = collect();
        foreach ($menuGroups as $group) {
            $smallGenres = collect();
            $small2Genres = collect();

            $genre = Genre::find($group->genre_id);
            if (!$genre) {
                continue;
            }

            $paths = explode('/', $genre->path);

            $middleGenre = (!empty($paths[2])) ? $paths[2] : '';
            $smallGenre = (!empty($paths[3])) ? $paths[3] : '';

            if (!empty($middleGenre)) {
                $smallGenres = Genre::getListByPath($menu->app_cd, $middleGenre, 3)->get();
            }

            if (!empty($smallGenre)) {
                $small2Genres = Genre::getListByPath($menu->app_cd, $smallGenre, 4)->get();
            } else {
                $smallGenre = $genre->genre_cd;
            }

            $genreGroups->push([
                'genreGroupId' => $group->id,
                'genre' => $genre,
                'middleGenre' => $middleGenre,
                'smallGenre' => $smallGenre,
                'smallGenres' => $smallGenres,
                'small2Genres' => $small2Genres
            ]);
        }

        return view('admin.Menu.Genre.edit', [
            'menuName' => $menu->name,
            'menuPublished' => $menu->published,
            'appCd' => $menu->app_cd,
            'bigGenre' => $this->COOKING,
            'middleGenres' => $middleGenres,
            'genreGroups' => $genreGroups,
            'id' => $id,
        ]);
    }

    public function edit(MenuGenreEditRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $appCd = $request->input('app_cd');
            $genreGroupId = $request->input('genre_group_id');
            $middleGenre = $request->input('middle_genre');
            $smallGenre = $request->input('small_genre');
            $small2Genre = $request->input('small2_genre', '');

            $path = [];
            $count = count($middleGenre);
            for ($i = 0; $i < $count; ++$i) {
                if (is_null($small2Genre[$i])) {
                    $path[$i] = sprintf('/%s/%s', $this->COOKING, $middleGenre[$i]);
                    $genres = Genre::getGenreMenu($path[$i], $appCd, $smallGenre[$i])->get();
                } else {
                    $path[$i] = sprintf('/%s/%s/%s', $this->COOKING, $middleGenre[$i], $smallGenre[$i]);
                    $genres = Genre::getGenreMenu($path[$i], $appCd, $small2Genre[$i])->get();
                }

                if ($genres->count() == 0) {
                    \DB::rollBack();
                    return redirect(sprintf('admin/menu/%d/genre/edit', $id))
                    ->with('custom_error', sprintf('ジャンルが存在しませんでした'));
                }

                $genre = $genres->pop();
                $genreGroup = GenreGroup::find($genreGroupId[$i]);

                if (!$genreGroup) {
                    // 新規追加
                    $genreGroup = GenreGroup::firstOrNew([
                        'genre_id' => $genre->id,
                        'menu_id' => $id
                    ]);
                    if (!empty($genreGroup->id)) {
                        \DB::rollBack();
                        return redirect(route('admin.menu.genre.edit', ['id' => $id]))
                        ->with('custom_error', sprintf('既に登録済みです'));
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

            return redirect(sprintf('admin/menu/%d/genre/edit', $id))
            ->with('custom_error', sprintf('ジャンルを保存できませんでした'));
        }

        return redirect(sprintf('admin/menu/%d/genre/edit', $id))
           ->with('message', sprintf("ジャンルを保存しました"));
    }

    /**
     * 管理画面 - メニュージャンル(追加画面)　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param integer $id
     * @return void
     */
    public function addForm(int $id)
    {
        $menu = Menu::find($id);
        $middleGenres = Genre::getListByPath($menu->app_cd, $this->COOKING, 2)->get();

        return view('admin.Menu.Genre.add', [
            'menu' => $menu,
            'middleGenres' => $middleGenres,
            'bigGenre' => $this->COOKING,
        ]);
    }

    /**
     * 管理画面 - メニュージャンル(追加)　（※追加方法の仕様変更変更に伴い、本関数は未使用）
     *
     * @param MenuGenreAddRequest $request
     * @param integer $id
     * @return void
     */
    public function add(MenuGenreAddRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $appCd = $request->input('app_cd');
            $middleGenre = $request->input('middle_genre');
            $smallGenre = $request->input('small_genre');
            $small2Genre = $request->input('small2_genre', '');

            if (empty($small2Genre)) {
                $path = sprintf('/%s/%s', $this->COOKING, $middleGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $smallGenre)->get();
            } else {
                $path = sprintf('/%s/%s/%s', $this->COOKING, $middleGenre, $smallGenre);
                $genres = Genre::getGenreMenu($path, $appCd, $small2Genre)->get();
            }

            if ($genres->count() == 0) {
                return redirect(sprintf('admin/menu/%d/genre/add', $id))
                ->with('custom_error', sprintf("ジャンルが存在しませんでした"));
            }

            $genre = $genres->pop();
            $genreGroup = GenreGroup::firstOrNew([
            'genre_id' => $genre->id,
            'menu_id' => $id
        ]);

            if (!empty($genreGroup->id)) {
                return redirect(sprintf('admin/menu/%d/genre/add', $id))
                ->with('custom_error', sprintf("既に登録済みです"));
            }

            $genreGroup->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect(sprintf('admin/menu/%d/genre/edit', $id))
            ->with('custom_error', sprintf('ジャンルを追加できませんでした'));
        }

        return redirect(sprintf('admin/menu/%d/genre/edit', $id))
            ->with('message', sprintf("ジャンルを追加しました"));
    }

    public function delete(int $id)
    {
        try {
            GenreGroup::destroy($id);
            return response()->json(['result' => 'ok']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
