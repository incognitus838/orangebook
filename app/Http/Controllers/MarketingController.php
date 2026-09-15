<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class MarketingController extends Controller
{
    public function home()
    {
        $plans = Plan::where('is_active', 1)->orderBy('order')->get();
        return view('marketing.home', ['plans' => $plans]);
    }
}
