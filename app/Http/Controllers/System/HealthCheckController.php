<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;

class HealthCheckController extends Controller
{
    public function index()
    {
        return 'Health check OK';
    }
}
