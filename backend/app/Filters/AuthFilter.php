<?php

namespace App\Filters;

use App\Models\SystemAccessModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Re-checks is_active and role flags on EVERY authenticated request, per
 * CLAUDE.md — session data can go stale the moment a native_admin revokes
 * or changes someone's access mid-session, since that revocation is written
 * straight to system_access with no way to push it into an already-issued
 * session. login() only checks the inactivity threshold once, at login,
 * because last_signed_in only changes at login; this filter is the other
 * half — catching a manual revoke or a role change at any arbitrary moment.
 *
 * CARHRIS/system_access sessions only. native_admin sessions have their own
 * dedicated AdminAuthFilter instead of a branch in here — see that class's
 * docblock for why they're kept separate rather than unified.
 *
 * Runs CsrfFilter::verify() as its own first step, and CsrfFilter::
 * exposeToken() on every response it produces (success or its own
 * rejection) — see CsrfFilter's docblock for why that composition is
 * required here rather than relying on CsrfFilter running separately.
 */
class AuthFilter implements FilterInterface
{
	public function before(RequestInterface $request, $arguments = null)
	{
		if ($csrfRejection = CsrfFilter::verify($request)) {
			return $csrfRejection;
		}

		if (!session()->get('isLoggedIn')) {
			return $this->reject('Not authenticated.', 401);
		}

		$carhrisEmpId = session()->get('carhris_emp_id');
		$access       = (new SystemAccessModel())->findByCarhrisId((string) $carhrisEmpId);

		if (!$access || !$access->is_active) {
			// The grant is gone or was just revoked — don't leave a session
			// alive that no longer maps to anything real.
			session()->destroy();

			return $this->reject('Access denied. Please contact your system administrator if you believe this is an error.', 403);
		}

		// Refreshed every request, not just at login — a promotion/demotion
		// takes effect on the person's very next request, not their next
		// login.
		session()->set('roles', [
			'is_division_chief'    => (bool) $access->is_division_chief,
			'is_document_handler'  => (bool) $access->is_document_handler,
			'is_regional_director' => (bool) $access->is_regional_director,
		]);
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		CsrfFilter::exposeToken($response);
	}

	private function reject(string $message, int $status)
	{
		$response = service('response')
			->setStatusCode($status)
			->setJSON(['messages' => ['error' => $message]]);

		CsrfFilter::exposeToken($response);

		return $response;
	}
}
