<?php

namespace App\Http\Controllers;

use App\Services\JadwalViewService;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function index(Request $request, JadwalViewService $jadwalViewService)
    {
        return view('welcome', $jadwalViewService->buildPublic($request));
    }
}
