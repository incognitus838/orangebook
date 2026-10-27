<?php

namespace App\Http\Controllers;

class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home');
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }
}
