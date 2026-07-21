<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index', [
            'products' => $this->catalogProducts(),
        ]);
    }

    public function mobile()
    {
        return view('home.index', [
            'products' => $this->catalogProducts(),
            'hideStaffLinks' => true,
            'hideAppDownload' => true,
            'inquirySource' => 'mobile',
        ]);
    }

    private function catalogProducts()
    {
        return Product::orderBy('name')->limit(500)->get();
    }
}