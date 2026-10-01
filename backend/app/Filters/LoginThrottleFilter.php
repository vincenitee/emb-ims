<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate-limits login attempts, keyed two ways at once:
 *
 * - Per IP: catches scripted/spray attacks. Deliberately loose (not a
 *   handful of attempts) because employees here likely sit behind shared
 *   office NAT -- a strict per-IP limit would risk locking out an entire
 *   office over one person mistyping their password a few times.
 * - Per submitted username: catches someone targeting one specific account,
 *   even across many rotating IPs (which the IP-based check alone can't
 *   see). Tighter, since a legitimate user rarely fails their OWN login
 *   more than a couple of times in a short window.
 *
 * Both use CI4's built-in Throttler (token bucket, backed by the
 * configured cache handler -- file cache here, no extra infra needed).
 * Applied only to login routes -- see Routes.php for why the other
 * endpoints don't need this.
 *
 * Cache keys are md5-hashed, not used raw. The file cache handler stores
 * each key as a filename, and an IPv6 address (e.g. '::1') contains colons
 * -- illegal in Windows filenames. cache->save() fails silently on that
 * (no exception, just returns false), which made Throttler::check() take
 * its "bucket doesn't exist yet" branch forever and never actually block
 * anything. Hashing sidesteps this on every platform, not just Windows.
 *
 * Runs CsrfFilter::verify() as its own first step, and CsrfFilter::
 * exposeToken() on every response it produces (success or its own
 * rejection) — see CsrfFilter's docblock for why that composition is
 * required here rather than relying on CsrfFilter running separately.
 * Checking CSRF before throttling doesn't weaken the throttle: a request
 * with no valid token can never succeed at guessing a password anyway, so
 * there's nothing lost in not charging it against the budget meant for
 * requests that actually got that far.
 */
class LoginThrottleFilter implements FilterInterface
{
	private const IP_CAPACITY   = 10;
	private const IP_SECONDS    = 60;
	private const USER_CAPACITY = 5;
	private const USER_SECONDS  = 60;

	public function before(RequestInterface $request, $arguments = null)
	{
		if ($csrfRejection = CsrfFilter::verify($request)) {
			return $csrfRejection;
		}

		$throttler = service('throttler');

		$ip = $request->getIPAddress();

		if (!$throttler->check('login_ip_' . md5($ip), self::IP_CAPACITY, self::IP_SECONDS)) {
			return $this->tooMany();
		}

		$body     = json_decode($request->getBody(), true);
		$username = $body['username'] ?? null;

		if ($username && !$throttler->check('login_user_' . md5(strtolower($username)), self::USER_CAPACITY, self::USER_SECONDS)) {
			return $this->tooMany();
		}
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		CsrfFilter::exposeToken($response);
	}

	private function tooMany()
	{
		$response = service('response')
			->setStatusCode(429)
			->setJSON(['messages' => ['error' => 'Too many attempts. Please wait a moment and try again.']]);

		CsrfFilter::exposeToken($response);

		return $response;
	}
}
