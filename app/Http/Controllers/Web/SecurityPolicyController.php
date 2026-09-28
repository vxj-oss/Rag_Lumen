<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SecurityPolicyController extends Controller
{
    public function index(): View
    {
        return view('legal.html.security-policy');
    }
}
