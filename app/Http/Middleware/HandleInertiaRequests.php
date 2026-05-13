<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Lunar\Facades\CartSession;
use Lunar\Models\Collection;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (str_starts_with($request->path(), 'admin')) {
            return parent::share($request);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'cart' => [
                'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'mpPublicKey' => config('services.mercadopago.public_key'),
            'navCollections' => fn () => Collection::whereNull('parent_id')
                ->with('urls')
                ->get()
                ->map(fn (Collection $col) => [
                    'name' => $col->translateAttribute('name'),
                    'slug' => $col->urls->first()?->slug,
                ])
                ->filter(fn (array $col) => $col['slug'])
                ->values(),
        ]);
    }
}
