<?php

namespace App\Http\Controllers\Web\Legal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SecurityPolicyController extends Controller
{
    public function index(): View
    {
        return view('legal.security-policy');
    }
}
