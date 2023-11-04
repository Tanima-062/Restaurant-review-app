<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Favorite;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    private $favorite;

    function __construct(Favorite $favorite)
    {
        $this->favorite = $favorite;
    }

    public function index()
    {
        return $this->favorite->all();
    }
}
