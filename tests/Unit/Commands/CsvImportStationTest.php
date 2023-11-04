<?php

namespace Tests\Unit\Commands;

use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CsvImportStationTest extends TestCase
{
    private $folder = 'app/upload';

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCsvImportStation()
    {
        // テスト用ファイル作成
        $mkdirFlg = false;
        if (!file_exists(storage_path($this->folder))) {
            mkdir(storage_path($this->folder), 0700);
            $mkdirFlg = true;
        }
        $this->_createFile('testCsvImportStation.csv');

        // 更新用テストデータ作成
        $addStation = $this->_createStation('999999990');
        $addStation2 = $this->_createStation('999999991', '2022-10-01');
        $station = Station::whereIn('station_cd', ['999999990', '999999991', '999999992'])->get();

        // テスト１（通常動作新規追加、更新）
        // バッチ実行
        $this->artisan('import:csv testCsvImportStation.csv')
            ->expectsOutput('[CsvImportStation] ##### START #####')
            ->expectsOutput(0);

        // 起動結果
        $station = Station::whereIn('station_cd', ['999999990', '999999991', '999999992'])->get();

        // 更新されているか
        $this->assertSame($addStation->id, $station[0]['id']);
        $this->assertSame('teststation1', $station[0]['name']);
        $this->assertSame('test_station01', $station[0]['name_roma']);
        $this->assertSame(1, $station[0]['prefecture_id']);
        $this->assertSame(100.0, $station[0]['longitude']);
        $this->assertSame(200.0, $station[0]['latitude']);
        $this->assertTrue(!empty($station[0]['deleted_at']));   // nullから値が更新されている

        $this->assertSame($addStation2->id, $station[1]['id']);
        $this->assertSame('teststation2', $station[1]['name']);
        $this->assertSame('test_station02', $station[1]['name_roma']);
        $this->assertSame(1, $station[1]['prefecture_id']);
        $this->assertSame(300.0, $station[1]['longitude']);
        $this->assertSame(400.0, $station[1]['latitude']);
        $this->assertNull($station[1]['deleted_at']);       // nullに変更されている

        // 追加されているか
        $this->assertSame('teststation3', $station[2]['name']);
        $this->assertSame('test_station03', $station[2]['name_roma']);
        $this->assertSame(2, $station[2]['prefecture_id']);
        $this->assertSame(500.0, $station[2]['longitude']);
        $this->assertSame(600.0, $station[2]['latitude']);
        $this->assertTrue(!empty($station[2]['deleted_at']));

        // テスト2（skipテスト）
        // バッチ実行
        $this->artisan('import:csv testCsvImportStation.csv')
        ->expectsOutput('[CsvImportStation] ##### START #####')
        ->expectsOutput(0);

        // 起動結果(更新されていないこと)
        $station2 = Station::whereIn('station_cd', ['999999990', '999999991', '999999992'])->get();
        $this->assertEquals($station[0]['updated_at'], $station2[0]['updated_at']);
        $this->assertEquals($station[1]['updated_at'], $station2[1]['updated_at']);
        $this->assertEquals($station[2]['updated_at'], $station2[2]['updated_at']);

        // テスト3（フォーマットが規定外のエラーの場合）
        $this->_createFile2('testCsvImportStation2.csv');

        // バッチ実行
        $this->artisan('import:csv testCsvImportStation2.csv')
        ->expectsOutput('[CsvImportStation] ##### START #####')
        ->expectsOutput(0);

        // 起動結果(更新されていないこと)
        $station3 = Station::whereIn('station_cd', ['999999990'])->get();
        $this->assertEquals($station[0]['updated_at'], $station3[0]['updated_at']);

        // テスト用ファイル削除
        $this->_deleteFile('testCsvImportStation.csv');
        $this->_deleteFile('testCsvImportStation2.csv');
        if ($mkdirFlg) {
            rmdir(storage_path('app/upload'));
        }
    }

    private function _createFile($fileName)
    {
        if (file_exists(storage_path($fileName))) {
            $this->_deleteFile($fileName);
        }
        $filePath = storage_path($this->folder . '/' . $fileName);
        touch($filePath);

        $current = file_get_contents($filePath);
        $current .= "station_cd, ,name,,name_roma,,prefecture_id,,,longitude,latitude,,,delete_flag,\n";
        $current .= "999999990,,teststation1,,test-station01,,1,,,100,200,,,2,\n";
        $current .= "999999991,,teststation2,,test-station02,,1,,,300,400,,,0,\n";
        $current .= "999999992,,teststation3,,test-station03,,2,,,500,600,,,2,\n";
        $current .= "\n";

        file_put_contents($filePath, $current);
    }

    private function _createFile2($fileName)
    {
        if (file_exists(storage_path($fileName))) {
            $this->_deleteFile($fileName);
        }
        $filePath = storage_path($this->folder . '/' . $fileName);
        touch($filePath);

        $current = file_get_contents($filePath);
        $current .= "station_cd, ,name,,name_roma,,prefecture_id,,,longitude,latitude,,,delete_flag,\n";
        $current .= "999999990,2,2,\n";
        $current .= "\n";

        file_put_contents($filePath, $current);
    }

    private function _deleteFile($fileName)
    {
        $filePath = storage_path($this->folder . '/' . $fileName);
        unlink($filePath);
    }

    private function _createStation($stationCd, $deletedAt=null)
    {
        $station = new Station();
        $station->name = 'test';
        $station->station_cd = $stationCd;
        $station->deleted_at = $deletedAt;
        $station->save();
        return $station;
    }
}
