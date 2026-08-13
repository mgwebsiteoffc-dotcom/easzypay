<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class HomeController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('marketing.home', compact('plans'));
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('marketing.pricing', compact('plans'));
    }

    public function features()
    {
        return view('marketing.features');
    }

    public function about()
    {
        return view('marketing.about');
    }

    public function contact()
    {
        return view('marketing.contact');
    }

    public function blog()
    {
        return view('marketing.blog');
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }

    public function changelog()
    {
        return view('marketing.changelog');
    }
}