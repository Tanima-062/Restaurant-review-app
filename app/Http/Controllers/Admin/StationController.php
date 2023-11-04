<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StationSearchRequest;
use App\Models\Station;
use Illuminate\Http\Request;
use Cache;
use Illuminate\Support\Facades\Storage;
use Log;

class StationController extends Controller
{
    const CSV_FILE = 'station.csv';

    public function index(StationSearchRequest $request)
    {
        $stations = Station::adminSearchFilter($request->validated())->sortable()->paginate(30);
        return view('admin.Station.index', ['stations' => $stations]);
    }

    public function upload(Request $request)
    {
        if ($this->existPid()) {
            return redirect('admin/station')->with('message', '前回のインポート処理中のため処理を開始できません。');
        }

        Storage::delete('upload/'.self::CSV_FILE);
        $request->file('file')->storeAs('upload', self::CSV_FILE);

        exec("nohup php /var/www/artisan import:csv ".self::CSV_FILE." > /dev/null & echo $!", $out);

        Log::debug('create pid => '.$out[0]);
        // 2重起動防止用にpidを保存しておく(1時間)。↑のコマンドが終わったら(正常でも異常でも)、pidはcacheから削除される
        Cache::put(self::CSV_FILE, $out[0], 60 * 60);

        Log::info('start csv import');

        return redirect('admin/station')->with('message', 'CSVインポートを開始しました。csvファイルが10000行程あると終了まで1時間ほどかかります。');
    }

    private function existPid()
    {
        $pid = Cache::get(self::CSV_FILE, 0);
        Log::debug('Get cache pid => '.$pid);

        // 2重起動チェック
        if ($pid > 0) {
            $cmd = "ps h " . $pid;
            exec($cmd, $output, $result);
            if (count($output) > 0) { // 二重起動
                Log::info('double start pid '.$pid);
                return true;
            }
        }

        return false;
    }

    public function nowStatus()
    {
        if ($this->existPid()) {
            return redirect('admin/station')->with('message', '前回のインポート処理中のため処理を開始できません。');
        } else {
            return redirect('admin/station')->with('message', '前回のcsvインポートは終了しています。');
        }
    }
}
