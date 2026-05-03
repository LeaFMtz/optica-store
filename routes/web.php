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
use App\Http\Controllers\CheckoutPlaceController;
use App\Http\Controllers\CheckoutShippingController;
use App\Http\Controllers\CheckoutSuccessController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundPolicyController;
use App\Http\Controllers\SearchController;
// use App\Livewire\CheckoutPage;       // Wave 6: replaced by CheckoutController
// use App\Livewire\CheckoutSuccessPage; // Wave 6: replaced by CheckoutSuccessController
// use App\Livewire\ContactPage;   // migrated to ContactController (Wave 2)
// use App\Livewire\FaqPage;       // migrated to FaqController (Wave 2)
// use App\Livewire\CollectionPage;    // migrated to CollectionController (Wave 3)
// use App\Livewire\Home;              // migrated to HomeController (Wave 1)
// use App\Livewire\ProductPage;       // migrated to ProductController (Wave 4)
// use App\Livewire\RefundPolicyPage;  // migrated to RefundPolicyController (Wave 2)
// use App\Livewire\SearchPage;        // migrated to SearchController (Wave 3)
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', Home::class);       // Wave 1: replaced
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

// Cart JSON endpoints (non-Inertia, web middleware for session/CSRF)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/lines', [CartController::class, 'store'])->name('lines.store');
    Route::patch('/lines/{id}', [CartController::class, 'update'])->name('lines.update');
    Route::delete('/lines/{id}', [CartController::class, 'destroy'])->name('lines.destroy');
});

// Route::get('checkout', CheckoutPage::class)->name('checkout.view');  // Wave 6: replaced
Route::get('checkout', CheckoutController::class)->name('checkout.view');

// Checkout JSON endpoints (auth required, web middleware for session/CSRF)
Route::middleware('auth')->prefix('checkout')->name('checkout.')->group(function () {
    Route::post('/address', CheckoutAddressController::class)->name('address');
    Route::post('/shipping', CheckoutShippingController::class)->name('shipping');
    Route::post('/place', CheckoutPlaceController::class)->name('place');
});

// Route::get('checkout/success', CheckoutSuccessPage::class)->name('checkout-success.view');  // Wave 6: replaced
Route::get('checkout/success', CheckoutSuccessController::class)->name('checkout-success.view');

// Route::get('contacto', ContactPage::class)->name('contact.view');  // Wave 2: replaced
Route::get('contacto', [ContactController::class, 'show'])->name('contact.view');
Route::post('contacto', [ContactController::class, 'send'])->name('contact.send');

// Route::get('preguntas-frecuentes', FaqPage::class)->name('faq.view');  // Wave 2: replaced
Route::get('preguntas-frecuentes', FaqController::class)->name('faq.view');

// Route::get('politica-de-devolucion', RefundPolicyPage::class)->name('refund-policy.view');  // Wave 2: replaced
Route::get('politica-de-devolucion', RefundPolicyController::class)->name('refund-policy.view');
