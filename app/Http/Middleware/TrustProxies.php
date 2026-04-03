<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $config = config('app.trusted_proxies');

        if (empty($config) && !app()->environment('local')) {
            throw new \Error('Trusted proxies must be configured for non-local environments.');
        }

        if (empty($config)) {
            $this->proxies = '*';
        } elseif (!empty($config)) {
            $this->proxies = explode(',', $config);
        } else {
            $this->proxies = '*';
        }
    }
}
