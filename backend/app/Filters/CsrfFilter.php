<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Config\Services;

/**
 * CI4's built-in double-submit-cookie CSRF protection, wrapped for this SPA.
 *
 * CI4 4.0.5 hardcodes its CSRF cookie as HttpOnly (see
 * Security::sendCookie() -- 'httponly' => true, no config toggle exists in
 * this version to change that). That means the frontend can't read the
 * token via document.cookie the way axios's built-in XSRF handling expects
 * to. The server can still read it fine (only JS is blocked by HttpOnly),
 * so verification is unaffected -- what changes is delivery: the current
 * token is exposed as a plain response header on every response (see
 * exposeToken()), and the frontend remembers the most recent value and
 * echoes it back as the same header name on its next mutation.
 *
 * verify()/exposeToken() are static and public so other filters
 * (AuthFilter, AdminAuthFilter, LoginThrottleFilter) can call them
 * directly as their own first step, rather than relying on this filter
 * running separately. That's not stylistic -- it's required. Route-
 * specific filters run BEFORE global ones in this CI4 version (confirmed
 * while building rate-limiting), so if this were only registered
 * globally, any route that ALSO carries a route-specific filter
 * (AuthFilter etc.) would never reach this filter at all when that other
 * filter rejects first -- an unauthenticated GET to a protected route
 * would come back with no token, leaving the frontend with nothing to
 * send on its next mutation. Composing verify()/exposeToken() directly
 * into every gating filter's own before()/after() closes that gap
 * everywhere at once, rather than patching each filter's rejection path
 * individually (which was tried first here and found insufficient --
 * exposing the hash value alone doesn't help if the matching cookie was
 * never actually sent, and only Security::verify()'s internal, protected
 * sendCookie() call does that).
 *
 * This class is still registered directly as a route filter too, for
 * routes with no other filter competing for the one-filter-per-route slot
 * (logout, admin/logout) -- see Routes.php.
 */
class CsrfFilter implements FilterInterface
{
	/**
	 * Runs CSRF verification. Returns null if it's fine to proceed, or a
	 * ready-to-send rejection Response if not -- callers should return
	 * that Response immediately to short-circuit, exactly like any other
	 * filter's before().
	 */
	public static function verify(RequestInterface $request): ?ResponseInterface
	{
		if ($request->isCLI()) {
			return null;
		}

		try {
			Services::security()->verify($request);

			return null;
		} catch (SecurityException $e) {
			return service('response')
				->setStatusCode(403)
				->setJSON(['messages' => ['error' => 'Your session token is invalid or has expired. Please refresh and try again.']]);
		}
	}

	/**
	 * Stamps the current valid token onto a response as a header. Callers
	 * should call this on EVERY response they send -- success or their own
	 * rejection -- so the frontend always has a fresh token to use next,
	 * regardless of which filter/branch produced this particular response.
	 */
	public static function exposeToken(ResponseInterface $response): void
	{
		$response->setHeader(config('App')->CSRFHeaderName, Services::security()->getHash());
	}

	public function before(RequestInterface $request, $arguments = null)
	{
		return static::verify($request);
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		if ($request->isCLI()) {
			return;
		}

		static::exposeToken($response);
	}
}
