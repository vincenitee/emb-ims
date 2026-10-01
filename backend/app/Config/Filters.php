<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use App\Filters\AdminAuthFilter;
use App\Filters\AuthFilter;
use App\Filters\CsrfFilter;
use App\Filters\LoginThrottleFilter;

class Filters extends BaseConfig
{
	/**
	 * Configures aliases for Filter classes to
	 * make reading things nicer and simpler.
	 *
	 * @var array
	 */
	public $aliases = [
		'csrf'     => CsrfFilter::class,
		'toolbar'  => DebugToolbar::class,
		'honeypot' => Honeypot::class,
		'auth'     => AuthFilter::class,
		'adminAuth' => AdminAuthFilter::class,
		'loginThrottle' => LoginThrottleFilter::class,
	];

	/**
	 * List of filter aliases that are always
	 * applied before and after every request.
	 *
	 * @var array
	 */
	/**
	 * 'csrf' is deliberately NOT global here. It's composed directly into
	 * AuthFilter/AdminAuthFilter/LoginThrottleFilter (each calls
	 * CsrfFilter::verify()/exposeToken() as its own first/last step -- see
	 * CsrfFilter's docblock for why), and attached directly, per-route, to
	 * routes that carry no other filter (logout, admin/logout -- see
	 * Routes.php). Registering it here too would run Security::verify()
	 * a second time on the same request for any route that also has one of
	 * those other filters, double-regenerating the token pointlessly.
	 */
	public $globals = [
		'before' => [
			// 'honeypot',
		],
		'after'  => [
			'toolbar',
			// 'honeypot',
		],
	];

	/**
	 * List of filter aliases that works on a
	 * particular HTTP method (GET, POST, etc.).
	 *
	 * Example:
	 * 'post' => ['csrf', 'throttle']
	 *
	 * @var array
	 */
	public $methods = [];

	/**
	 * List of filter aliases that should run on any
	 * before or after URI patterns.
	 *
	 * Example:
	 * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
	 *
	 * @var array
	 */
	public $filters = [];
}
