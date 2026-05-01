<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact/Index');
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'message' => 'required|string|min:10',
        ]);

        // Email sending / DB persistence can be added here.
        // For now we flash success and redirect back.
        session()->flash('success', '¡Gracias por contactarnos! Te responderemos a la brevedad.');

        return redirect()->route('contact.view');
    }
}
