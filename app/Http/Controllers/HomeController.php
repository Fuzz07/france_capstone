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

        $isMobileApp = $this->isMobileApp();

        return view('home.index', [
            'products' => $this->catalogProducts(),
            'hideAppDownload' => $isMobileApp ?: null,
            'hideStaffLinks' => $isMobileApp ?: null,
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

    private function isMobileApp(): bool
    {
        return session('is_mobile_app', false)
            || str_contains(request()->userAgent() ?? '', 'MerasUserApp');
    }

    private function catalogProducts()
    {
        return Product::orderBy('name')->limit(500)->get();
    }
}
