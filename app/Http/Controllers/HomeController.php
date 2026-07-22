<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        if (request()->has('fcm_token')) {
            session(['fcm_token' => request()->query('fcm_token')]);
        }

        return view('home.index', [
            'products' => $this->catalogProducts(),
        ]);
    }

    public function mobile()
    {
        if (request()->has('fcm_token')) {
            session(['fcm_token' => request()->query('fcm_token')]);
        }

        session(['is_mobile_app' => true]);

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
