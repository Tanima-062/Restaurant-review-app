<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AreaAddRequest;
use App\Http\Requests\Admin\AreaEditRequest;
use App\Http\Requests\Admin\AreaSearchRequest;
use App\Models\Area;
use Batch;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    protected $redirectTo = 'admin/area/';

    /**
     * 管理画面 - エリア一覧.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AreaSearchRequest $request)
    {
        $areas = Area::adminSearchFilter($request->validated())->sortable()->paginate(30);

        return view('admin.Area.index', [
            'areas' => $areas,
        ]);
    }

    /**
     * 管理画面 - エリア新規追加フォーム表示.
     *
     * @return view
     */
    public function addForm()
    {
        $bigAreas = Area::select('id', 'name', 'area_cd', 'path')
            ->where('level', 1)
            ->get();
        $middleAreas = Area::select('id', 'name', 'area_cd', 'path')
            ->where('level', 2)
            ->get();
        if (!empty(old('big_area'))) {
            $middleAreas = Area::getListByPath(old('big_area'), 2)->get();
        }

        return view('admin.Area.add', compact('bigAreas', 'middleAreas'));
    }

    /**
     * 管理画面 - エリア新規追加処理.
     *
     * @param Request $request
     */
    public function add(AreaAddRequest $request)
    {
        try {
            \DB::beginTransaction();

            // $requestの整形
            $data = $request->except(['_token', 'redirect_to']);
            $data['big_area'] === 'none' ? $data['big_area'] = null : '';

            // published設定
            $data['published'] = (!empty($data['published'])) ? 1 : 0;

            // path設定
            $path = $this->makePath($data);
            $data['path'] = $path;

            // 入力内容からlevelを設定
            $data['level'] = $this->setLevel($data, $path);

            $data['weight'] = (!empty($data['weight'])) ? $data['weight'] : 0;

            $data['sort'] = (!empty($data['sort'])) ? $data['sort'] : 0;

            $area = new Area();
            $area->create($data);
            
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();
            return redirect($this->redirectTo)
                ->with('custom_error', sprintf('エリア「%s」を作成できませんでした', $request->name));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf('エリア「%s」を作成しました', $data['name']));
    }

    /**
     * 管理画面 - エリア(編集).
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editForm(int $id)
    {
        $middleAreas = collect();

        $area = Area::find($id);

        $paths = explode('/', $area->path);
        $bigArea = (!empty($paths[1])) ? $paths[1] : '';
        $middleArea = (!empty($paths[2])) ? $paths[2] : '';
        $smallArea = (!empty($paths[3])) ? $paths[3] : '';

        $bigAreas = Area::select('id', 'name', 'area_cd', 'path')
            ->where('level', 1)
            ->get();

        if (!empty($bigArea)) {
            $middleAreas = Area::getListByPath($bigArea, 2)->get();
        }

        if (!empty(old('big_area'))) {
            $middleAreas = Area::getListByPath(old('big_area'), 2)->get();
        }

        return view('admin.Area.edit', [
            'area' => $area,
            'bigArea' => $bigArea,
            'bigAreas' => $bigAreas,
            'middleArea' => $middleArea,
            'middleAreas' => $middleAreas,
            'smallArea' => $smallArea,
        ]);
    }

    /**
     * 管理画面 - エリア編集処理.
     *
     * @param Request $request
     */
    public function edit(AreaEditRequest $request, int $id)
    {
        // $requestの整形
        $data = $request->except(['_token', 'redirect_to']);
        $data['big_area'] === 'none' ? $data['big_area'] = null : '';

        // published設定
        $data['published'] = (!empty($data['published'])) ? 1 : 0;

        // path設定
        $path = $this->makePath($data);

        // 編集前のpath設定
        $oldPathAreaCd = $this->makeOldPath($data);

        try {
            \DB::beginTransaction();
            
            $area = Area::find($id);

            // フォーム入力内容からlevelを設定
            $area->level = $this->setLevel($data, $path);

            // データ整形＆保存
            $area->name = $data['name'];
            $area->area_cd = $data['area_cd'];
            $area->path = $path;
            $area->published = $data['published'];
            $area->weight = $data['weight'];
            $area->sort = $data['sort'];
            $area->save();

            // 編集後のlevelが編集前のlevelより大きかった場合
            // (データ挿入後のDB内を見たいため$areaを一時保存してから下記条件で判定)
            if ($data['old_area_level'] < $area->level) {
                $searchForAreaCd = !isset($data['middle_area']) ? $data['big_area'] : $data['middle_area'];
                $cutPath = explode('/', $area['path']);
                switch ($area->level) {
                    case 3:
                        $area->level = $area->level - 2;
                        $newPath = '/'.$cutPath[$area->level];
                        break;
                    case 2:
                        $area->level = $area->level - 2;
                        $newPath = '/'.$cutPath[$area->level];
                        break;
                    default:
                        \DB::rollBack();
                        return redirect($this->redirectTo)
                            ->with('custom_error', sprintf('エリア「%s」を更新出来ませんでした。更新するためのPATH「%s」が存在しません。', $data['name'], $path));
                }

                // 編集後のpathが存在しているか判定
                if (isset($searchForAreaCd) && !Area::where('path', $newPath)->where('area_cd', $searchForAreaCd)->exists()) {
                    \DB::rollBack();
                    return redirect($this->redirectTo)
                    ->with('custom_error', sprintf('エリア「%s」を更新出来ませんでした。更新するためのPATH「%s」が存在しません。', $data['name'], $path));
                }
            }

            // // 編集データに関連したデータの特定
            $updates = [];
            $paths = Area::getStartWithPath($oldPathAreaCd, $area->level)->get();
            foreach ($paths as $p) {
                $exPath = explode('/', $p->path);
                if (isset($data['big_area'])) {
                    $data['old_area_cd'] === $data['area_cd'] ? $exPath[1] = $data['big_area'] : $exPath[$area->level] = $data['area_cd'];
                } else {
                    $exPath[$area->level] = $data['area_cd'];
                }

                // 公開/非公開の一括設定をするか(親エリアが非公開の時は、子エリアも一括非公開へ)
                if ($data['published'] === 0) {
                    $update = [
                        'id' => $p->id,
                        'path' => implode('/', $exPath),
                        'published' => $data['published'],
                    ];
                } else {
                    $update = [
                        'id' => $p->id,
                        'path' => implode('/', $exPath),
                    ];
                }
                $updates[] = $update;
            }

            // 編集データに関連したデータの一括編集
            $result = Batch::update(new Area(), $updates, 'id');
            \DB::commit();
        } catch (\Exception $e) {
            report($e);
            \DB::rollBack();

            return redirect($this->redirectTo)
                ->with('custom_error', sprintf('エリア「%s」の更新に失敗しました(%s)', $request->name, $e->getMessage()));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf('エリア「%s」を更新しました', $data['name']));
    }

    /**
     * Ajax エリアカテゴリ取得用.
     *
     * @return string
     */
    public function list(Request $request)
    {
        $areaCd = $request->input('area_cd');
        $parentValue = $request->input('parent_value', '');
        $level = $request->input('level');

        if (!empty($parentValue)) {
            $areaCd = $parentValue.'/'.$areaCd;
        }

        $areas = Area::getListByPath($areaCd, $level)->get();

        return json_encode(['ret' => $areas->toArray()]);
    }

    /**
     * 入力内容からlevelを設定.
     *
     * @param $data $path
     *
     * @return string
     */
    public function setLevel(array $data, string $path)
    {
        $pathExist = mb_substr($path, 2);
        $pathCount = mb_substr_count($path, '/');
        if (!$pathExist) {
            $data['level'] = $pathCount;
        } else {
            $data['level'] = $pathCount + 1;
        }

        return $data['level'];
    }

    /**
     * 入力内容からパスを生成.
     *
     * @return string
     */
    public function makePath(array $data)
    {
        $path = '/';
        if (!empty($data['big_area'])) {
            $path .= strtolower($data['big_area']);
        }

        if (!empty($data['middle_area'])) {
            $path .= '/'.$data['middle_area'];
        }

        return $path;
    }

    /**
     * 入力内容から編集可能か判定する用のパスを生成.
     *
     * @return string
     */
    public function makeOldPath(array $data)
    {
        $array = explode('/', $data['old_area_path']);
        $path = '/';
        if ($array[1] === '') {
            $path .= strtolower($data['old_area_cd']);
        } else {
            $path = $data['old_area_path'];
            $path .= '/';
            $path .= $data['old_area_cd'];
        }

        return $path;
    }
}
