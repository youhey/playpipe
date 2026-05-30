<?php

namespace App\Http\Controllers\Listen;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ListenHomeController extends Controller
{
    public function __invoke(Request $request, ListenEpisodeIndexController $index): View
    {
        return $index($request);
    }
}
