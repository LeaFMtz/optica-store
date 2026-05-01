<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return Inertia::render('Auth/Register');
    }
}
