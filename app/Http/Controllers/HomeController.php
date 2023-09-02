<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke()
    {
        $packages = Package::all();

        return view('index', compact('packages'));
    }
}
