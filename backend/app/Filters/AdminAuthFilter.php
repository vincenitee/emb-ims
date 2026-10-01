<?php

namespace App\Filters;

use App\Enums\UserTypes;
use App\Models\NativeAdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * native_admin equivalent of AuthFilter — re-checks native_admins.is_active
 * on every authenticated request, same reasoning as AuthFilter's docblock.
 *
 * Deliberately a separate class rather than a branch inside AuthFilter:
 * native_admins is its own identity system, entirely independent of
 * CARHRIS/system_access (per CLAUDE.md, "don't conflate this with
 * system_access"), and that separation is worth keeping at the filter layer
 * too — a change to one can't risk breaking the other's session validation,
 * and the role-restriction feature below only ever makes sense here (a
 * native_admin has one 'role' string; a CARHRIS session has several
 * independent boolean flags instead, so the same mechanism wouldn't map
 * cleanly onto both).
 *
 * Optional route filter argument restricts a route to specific roles, e.g.
 * 'filter' => 'adminAuth:superadmin'.
 *
 * Runs CsrfFilter::verify() as its own first step, and CsrfFilter::
 * exposeToken() on every response it produces (success or its own
 * rejection) — see CsrfFilter's docblock for why that composition is
 * required here rather than relying on CsrfFilter running separately.
 */
class AdminAuthFilter implements FilterInterface
{
	public function before(RequestInterface $request, $arguments = null)
	{
		if ($csrfRejection = CsrfFilter::verify($request)) {
			return $csrfRejection;
		}

		// isLoggedIn alone isn't enough -- it's a generic flag set by BOTH
		// login flows. A valid CARHRIS session also has isLoggedIn=true but
		// never sets admin_id, so without this user_type check, a CARHRIS
		// session hitting an admin-filtered route would pass this far with
		// admin_id === null, and NativeAdminModel::find(null) falls back to
		// returning EVERY row (CI4's find() treats a null id as findAll()),
		// crashing the is_active check below on an array instead of an
		// entity. Confirmed this exact failure live before adding this check.
		if (!session()->get('isLoggedIn') || session()->get('user_type') !== UserTypes::SYSTEM()->getValue()) {
			return $this->reject('Not authenticated.', 401);
		}

		$adminId = session()->get('admin_id');
		$admin   = (new NativeAdminModel())->find($adminId);

		if (!$admin || !$admin->is_active) {
			session()->destroy();

			return $this->reject('Access denied. Please contact your system administrator if you believe this is an error.', 403);
		}

		// Refreshed every request, not just at login -- a promotion/demotion
		// to/from superadmin takes effect on the person's very next request.
		session()->set('role', $admin->role);

		if ($arguments !== null && !in_array($admin->role, $arguments, true)) {
			return $this->reject('You do not have permission to perform this action.', 403);
		}
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
