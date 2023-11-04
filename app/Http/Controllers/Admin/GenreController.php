<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenreRequest;
use App\Http\Requests\Admin\GenreSearchRequest;
use App\Models\Genre;
use Batch;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    protected $redirectTo = 'admin/genre/';

    /**
     * 管理画面 - ジャンル一覧
     *
     * @param GenreSearchRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(GenreSearchRequest $request)
    {
        $genres = Genre::adminSearchFilter($request->validated())->sortable()->paginate(30);

        return view('admin.Genre.index', [
            'genres' => $genres,
        ]);
    }

    public function addForm()
    {
        return view('admin.Genre.add');
    }

    public function add(GenreRequest $request)
    {
        try {
            \DB::beginTransaction();
            $data = $request->except(['_token', 'redirect_to']);
            $data['published'] = (!empty($data['published'])) ? 1 : 0;

            $path = $this->makePath($data);
            $data['level'] = mb_substr_count($path, '/') + 1;

            if (Genre::where('path', $path)->where('genre_cd', $data['genre_cd'])->exists()) {
                return redirect($this->redirectTo)
                ->with('custom_error', sprintf("ジャンル「%s」を作成出来ませんでした。重複しています。", $data['name']));
            }

            $data['path'] = $path;
            $genre = new Genre();
            $genre->create($data);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($this->redirectTo)
                ->with('custom_error', sprintf('ジャンル「%s」を作成できませんでした', $request->name));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf("ジャンル「%s」を作成しました", $data['name']));
    }

    public function list(Request $request)
    {
        $genreCd = $request->input('genre_cd');
        $parentValue = $request->input('parent_value', '');
        $appCd = $request->input('app_cd', '');
        $level = $request->input('level');

        if (!empty($parentValue)) {
            $genreCd = $parentValue . '/' .$genreCd;
        }

        $genres = Genre::getListByPath($appCd, $genreCd, $level)->get();

        return json_encode(['ret' => $genres->toArray()]);
    }

    /**
     * 管理画面 - ジャンル(編集)
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editForm(int $id)
    {
        $middleGenres = collect();
        $smallGenres = collect();

        $genre = Genre::find($id);
        $paths = explode('/', $genre->path);

        $bigGenre = (!empty($paths[1])) ? $paths[1] : '';
        $middleGenre = (!empty($paths[2])) ? $paths[2] : '';
        $smallGenre = (!empty($paths[3])) ? $paths[3] : '';

        if (!empty($bigGenre)) {
            $middleGenres = Genre::getListByPath($genre->app_cd, $bigGenre, 2)->get();
        }

        if (!empty($middleGenre)) {
            $smallGenres = Genre::getListByPath($genre->app_cd, $middleGenre, 3)->get();
        }

        return view('admin.Genre.edit', [
            'genre' => $genre,
            'bigGenre' => $bigGenre,
            'middleGenre' => $middleGenre,
            'middleGenres' => $middleGenres,
            'smallGenre' => $smallGenre,
            'smallGenres' => $smallGenres
        ]);
    }

    public function edit(GenreRequest $request, int $id)
    {
        $data = $request->except(['_token', 'redirect_to']);
        $data['published'] = (!empty($data['published'])) ? 1 : 0;

        $path = $this->makePath($data);

        try {
            \DB::beginTransaction();

            if (Genre::where('path', $path)->where('genre_cd', $data['genre_cd'])->where('id', '!=', $id)->exists()) {
                return redirect($this->redirectTo)
                    ->with('custom_error', sprintf("ジャンル「%s」を更新出来ませんでした。重複しています。", $data['name']));
            }

            $data['path'] = $path;
            $genre = Genre::find($id);
            $oldGenreCd = $genre->genre_cd;
            $genre->name = $data['name'];
            $genre->genre_cd = $data['genre_cd'];
            $genre->app_cd = $data['app_cd'];
            $genre->path = $path;
            $genre->level = mb_substr_count($path, '/') + 1;
            $genre->published = $data['published'];
            $genre->save();

            $updates = [];
            $paths = Genre::getStartWithPath($data['app_cd'], $oldGenreCd, $genre->level)->get();
            foreach ($paths as $p) {
                $exPath = explode('/', $p->path);
                $exPath[$genre->level] = $data['genre_cd'];
                $update = [
                    'id' => $p->id,
                    'path' => implode('/', $exPath)
                ];
                $updates[] = $update;
            }

            $result = Batch::update(new Genre(), $updates, 'id');
            \Log::debug('update end : '.print_r($result, true));
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            \DB::rollBack();

            return redirect($this->redirectTo)
                ->with('custom_error', sprintf('ジャンル「%s」を更新に失敗しました(%s)', $request->name, $e->getMessage()));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf("ジャンル「%s」を更新しました", $data['name']));
    }

    private function makePath(array $data)
    {
        $path = '';
        if (!empty($data['big_genre'])) {
            $path .= '/'.strtolower($data['big_genre']);
        }

        if (!empty($data['middle_genre'])) {
            $path .= '/'.$data['middle_genre'];
        }

        if (!empty($data['small_genre'])) {
            $path .= '/'.$data['small_genre'];
        }
        return $path;
    }
}
