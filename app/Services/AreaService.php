<?php

namespace App\Services;

use App\Models\Area;

class AreaService
{
    public function __construct(
        Area $area
    ) {
        $this->area = $area;
    }

    /**
     * エリア一覧を取得する.
     *
     * @param string $areaCd
     * @param int    $optionLevel
     *
     * @return array
     */
    public function getArea(string $areaCd = null, int $optionLevel = null)
    {
        $res = [];
        //大エリアの取得(Level1から取得する場合)
        if (strtoupper($areaCd) === 'JAPAN') {
            // stores.area_idにはlevel2が紐づく前提
            if ($optionLevel === 2) {
                $list = Area::where('published', 1)
                ->where('level', 2)
                ->whereHas('stores', function ($query) {
                    $query->where('stores.published', 1);
                })
                ->orderBySort()
                ->get();
            } elseif (is_null($optionLevel) || $optionLevel === 1) {
                $tmpAreas = Area::where('published', 1)
                ->whereHas('stores', function ($query) {
                    $query->where('stores.published', 1);
                })->get();
                $whereIn = [];
                foreach ($tmpAreas as $area) {
                    $exdPath = explode('/', $area->path);
                    if (!in_array($exdPath[1], $whereIn)) {
                        $whereIn[] = $exdPath[1];
                    }
                }
                $list = Area::whereIn('area_cd', $whereIn)
                ->where('published', 1)
                ->orderBySort()
                ->get();
            }

            //DBにあるarea_cdを取得する場合
        } else {
            $list=$this->getChildAreaList($areaCd, $optionLevel);
        }

        foreach ($list as $key => $val) {
            $res['areas'][$key]['id'] = $val->id;
            $res['areas'][$key]['name'] = $val->name;
            $res['areas'][$key]['areaCd'] = $val->area_cd;
            $res['areas'][$key]['path'] = $val->path;
            $res['areas'][$key]['level'] = $val->level;
            $res['areas'][$key]['weight'] = $val->weight;
            $res['areas'][$key]['sort'] = $val->sort;
            if(strtoupper($areaCd) === 'JAPAN'){
                $res['areas'][$key]['childAreas'] = $this->getChildAreaList($val->area_cd, $optionLevel);
            }
        }

        return $res;
    }

    public function getAreaAdmin(array $params)
    {
        $query = Area::query();
        $query->where('path', 'like', '%'.$params['areaCd'].'%');
        $query->where('level', 2);
        $query->where('published', 1);
        $result = $query->get();

        return $result;
    }

    private function getChildAreaList(string $areaCd = null, int $optionLevel = null){
        $res=[];
        $query = Area::query();
        $area = Area::where('area_cd', $areaCd)->first();

        if (empty($area)) {
            return $res;
        }

        //lowerLevelとareaLevelのチェック
        if ($optionLevel > 1 && $area->level === 1) {
            $query->where('path', 'LIKE', $area->path.$area->area_cd.'%')
                ->where('level', '<=', $optionLevel + 1);
        //level4まで増えた時用
        } elseif ($optionLevel > 1) {
            if ($optionLevel === 2) {
                $query->where('path', 'LIKE', $area->path.'/'.$area->area_cd.'%')
                    ->where('level', '<=', $optionLevel + 2);
            } else {
                return $res;
            }
        } elseif ($area->level === 1) {
            $query->where('path', $area->path.$area->area_cd);
        } else {
            $query->where('path', $area->path.'/'.$area->area_cd);
        }

        $query->where('published', 1);

        // stores.publishedも見て公開されている店舗が存在すること
        $query->whereHas('stores', function ($query) {
            $query->where('stores.published', 1);
        });
        return $query->orderBySort()->get();
    }
}
