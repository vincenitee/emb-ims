<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Config\Services;

/**
 * Wraps CI4's built-in double-submit-cookie CSRF protection for this SPA.
 *
 * CI4 4.0.5 hardcodes its CSRF cookie as HttpOnly (see
 * Security::sendCookie() -- 'httponly' => true, no config toggle exists in
 * this version to change that). That means the frontend can't read the
 * token via document.cookie the way axios's built-in XSRF handling expects
 * to. The server can still read it fine (only JS is blocked by HttpOnly),
 * so verification below is unaffected -- what changes is delivery: the
 * current token is exposed as a plain response header on every response
 * (see after()), and the frontend is responsible for remembering the most
 * recent value and echoing it back as the same header name on its next
 * state-changing request. GET requests are never verified (see
 * Security::verify() -- only POST/PUT/PATCH/DELETE are), so the very first
 * GET the SPA makes (e.g. checking auth state on load) is what bootstraps
 * the first token before any mutation is attempted.
 *
 * A verification failure is converted to this API's normal JSON error
 * shape here, instead of letting CI4's own generic exception envelope
 * (a different shape from everything else in this API) leak through.
 */
class CsrfFilter implements FilterInterface
{
	public function before(RequestInterface $request, $arguments = null)
	{
		if ($request->isCLI()) {
			return;
		}

		try {
			Services::security()->verify($request);
		} catch (SecurityException $e) {
			return service('response')
				->setStatusCode(403)
				->setJSON(['messages' => ['error' => 'Your session token is invalid or has expired. Please refresh and try again.']]);
		}
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		if ($request->isCLI()) {
			return;
		}

		// Same header name in both directions is intentional: this is what
		// the server hands you the *current* token in (a response header);
		// the frontend echoes that same value back as a *request* header of
		// the same name on its next mutation. They're different HTTP
		// messages, so there's no collision -- just one name to remember.
		$response->setHeader(config('App')->CSRFHeaderName, Services::security()->getHash());
	}
}
