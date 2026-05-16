<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginStoreController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegisterStoreController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutAddressController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutPaymentController;
use App\Http\Controllers\CheckoutPlaceController;
use App\Http\Controllers\CheckoutShippingController;
use App\Http\Controllers\CheckoutSuccessController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LensConfigurationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundPolicyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShippingQuoteController;
use App\Http\Controllers\Webhooks\MercadoPagoController as MercadoPagoWebhookController;
use App\Http\Middleware\VerifyMercadoPagoSignature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', LoginController::class)->name('login')->middleware('guest');
Route::post('/login', LoginStoreController::class)->name('login.store')->middleware('guest');
Route::get('/register', RegisterController::class)->name('register')->middleware('guest');
Route::post('/register', RegisterStoreController::class)->name('register.store')->middleware('guest');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Route::get('/collections/{slug}', CollectionPage::class)->name('collection.view');  // Wave 3: replaced
Route::get('/collections/{slug}', CollectionController::class)->name('collection.view');

// Route::get('/productos', CatalogPage::class)->name('catalog.view');  // Wave 3: replaced
Route::get('/productos', CatalogController::class)->name('catalog.view');

// Route::get('/products/{slug}', ProductPage::class)->name('product.view');  // Wave 4: replaced
Route::get('/products/{slug}', ProductController::class)->name('product.view');

// Route::get('search', SearchPage::class)->name('search.view');  // Wave 3: replaced
Route::get('search', SearchController::class)->name('search.view');

Route::get('/lens-configurations', LensConfigurationController::class)->name('lens.configurations');

// Cart JSON endpoints (non-Inertia, web middleware for session/CSRF)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/lines', [CartController::class, 'store'])->name('lines.store');
    Route::patch('/lines/{id}', [CartController::class, 'update'])->name('lines.update');
    Route::delete('/lines/{id}', [CartController::class, 'destroy'])->name('lines.destroy');
});

// Route::get('checkout', CheckoutPage::class)->name('checkout.view');  // Wave 6: replaced
Route::get('checkout', CheckoutController::class)->name('checkout.view');

// Checkout JSON endpoints — guest-accessible save steps, auth required for payment
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::post('/address', CheckoutAddressController::class)->name('address');
    Route::post('/shipping', CheckoutShippingController::class)->name('shipping');
    Route::post('/place', CheckoutPlaceController::class)->name('place');  // deprecated: kept for rollback safety
    Route::post('/payment', CheckoutPaymentController::class)->name('payment');
});

// Shipping quote — no auth required, uses XSRF-TOKEN cookie pattern
Route::post('/api/shipping/quote', ShippingQuoteController::class)->name('api.shipping.quote');

// MercadoPago webhook — no CSRF (excluded in VerifyCsrfToken), signature verified via middleware
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->middleware(VerifyMercadoPagoSignature::class)
    ->name('webhooks.mercadopago');

// Route::get('checkout/success', CheckoutSuccessPage::class)->name('checkout-success.view');  // Wave 6: replaced
Route::get('checkout/success', CheckoutSuccessController::class)->name('checkout-success.view');

// Route::get('contacto', ContactPage::class)->name('contact.view');  // Wave 2: replaced
Route::get('contacto', [ContactController::class, 'show'])->name('contact.view');
Route::post('contacto', [ContactController::class, 'send'])->name('contact.send');

// Route::get('preguntas-frecuentes', FaqPage::class)->name('faq.view');  // Wave 2: replaced
Route::get('preguntas-frecuentes', FaqController::class)->name('faq.view');

// Route::get('politica-de-devolucion', RefundPolicyPage::class)->name('refund-policy.view');  // Wave 2: replaced
Route::get('politica-de-devolucion', RefundPolicyController::class)->name('refund-policy.view');
