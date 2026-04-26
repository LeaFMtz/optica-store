<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CheckoutPage;
use App\Livewire\CheckoutSuccessPage;
use App\Livewire\CollectionPage;
use App\Livewire\ContactPage;
use App\Livewire\FaqPage;
use App\Livewire\Home;
use App\Livewire\ProductPage;
use App\Livewire\RefundPolicyPage;
use App\Livewire\SearchPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/debug-proxy', function (Request $request) {
    return [
        'ip_real_usuario' => $request->ip(),
        'es_segura' => $request->secure() ? 'SÍ (HTTPS)' : 'NO (HTTP)',
        'url_generada' => url('/test'),
        'header_proto' => $request->header('X-Forwarded-Proto'),
        'all_headers' => $request->headers->all(),
    ];
});

Route::get('/', Home::class);

Route::get('/login', Login::class)->name('login')->middleware('guest');
Route::get('/register', Register::class)->name('register')->middleware('guest');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

use App\Livewire\CatalogPage;

Route::get('/collections/{slug}', CollectionPage::class)->name('collection.view');

Route::get('/productos', CatalogPage::class)->name('catalog.view');

Route::get('/products/{slug}', ProductPage::class)->name('product.view');

Route::get('search', SearchPage::class)->name('search.view');

Route::get('checkout', CheckoutPage::class)->name('checkout.view');

Route::get('checkout/success', CheckoutSuccessPage::class)->name('checkout-success.view');

Route::get('contacto', ContactPage::class)->name('contact.view');

Route::get('preguntas-frecuentes', FaqPage::class)->name('faq.view');

Route::get('politica-de-devolucion', RefundPolicyPage::class)->name('refund-policy.view');
