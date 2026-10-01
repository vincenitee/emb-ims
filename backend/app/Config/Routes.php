<?php

namespace Config;

use App\Models\CarhrisUserModel;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
	// CARHRIS user authentication routes
	$routes->post('login', 'AuthController::login', ['as' => 'api.login', 'filter' => 'loginThrottle']);
	$routes->get('me', 'AuthController::me', ['as' => 'api.me', 'filter' => 'auth']);

	// No 'auth' filter on logout, deliberately -- it must stay callable even
	// when the session is missing/invalid, so a client can always recover to
	// a clean logged-out state rather than getting stuck behind a 401. Still
	// carries 'csrf' though -- it's a mutation like any other, no exception.
	$routes->post('logout', 'AuthController::logout', ['as' => 'api.logout', 'filter' => 'csrf']);

	// NATIVE admins authentication routes
	$routes->post('admin/login', 'AdminAuthController::login', ['as' => 'api.admin.login', 'filter' => 'loginThrottle']);
	$routes->get('admin/me', 'AdminAuthController::me', ['as' => 'api.admin.me', 'filter' => 'adminAuth']);
	$routes->post('admin/logout', 'AdminAuthController::logout', ['as' => 'api.admin.logout', 'filter' => 'csrf']);

	// Manual, superadmin-only password reset for another admin's account --
	// deliberately not self-service (no email/token flow). Restricted via
	// the adminAuth filter's role argument, checked against native_admins.role.
	$routes->post('admin/reset-password', 'AdminAuthController::resetAdminPassword', ['as' => 'api.admin.resetPassword', 'filter' => 'adminAuth:superadmin']);
});


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
