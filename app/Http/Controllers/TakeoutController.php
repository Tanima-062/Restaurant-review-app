<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\Fax\Fax;

class TakeoutController extends Controller
{
    public function search()
    {
        $a = 'test';

        return 'API Test OKKKK';
    }

    public function upload(Request $request)
    {
        $file = $request->file('fname');
        \Storage::disk('gcs')->putFile('', $file);
    }

    public function upForm()
    {
        return view('up_form');
    }

    public function pdf()
    {
        $fax = new Fax();
        $fax->store(285);

        return;
    }
}
