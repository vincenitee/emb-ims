<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

/*
 * --------------------------------------------------------------------
 * CORS
 * --------------------------------------------------------------------
 * pre_system fires before routing and before the filter pipeline --
 * deliberately. No 'OPTIONS' route is registered for any endpoint, so a
 * browser's preflight request would otherwise 404 before anything else
 * gets a chance to run. This also means these headers reach EVERY
 * eventual response (success, a filter rejection, an exception) since
 * nothing downstream clears a header set this early -- unlike a global
 * 'after' filter, which a route-specific filter's rejection can skip
 * entirely (same ordering quirk CsrfFilter's docblock explains).
 */
Events::on('pre_system', function () {
	// Skipped under the test environment, same reasoning as the output-
	// buffering guard in the next listener below: PHPUnit has already
	// written output of its own (the '.' progress dots) by the time this
	// runs, so any header() call here would throw "headers already sent"
	// (promoted to a hard failure by phpunit.xml's
	// convertWarningsToExceptions). CORS is meaningless in a test run
	// anyway -- there's no real browser and no real response being sent.
	if (ENVIRONMENT === 'testing')
	{
		return;
	}

	$origin = config('App')->corsAllowedOrigin;

	if (!empty($origin))
	{
		header("Access-Control-Allow-Origin: {$origin}");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, ' . config('App')->CSRFHeaderName);
		// Allow-Headers controls what the browser may SEND; it says nothing
		// about what JS is allowed to READ back off the response. By spec, a
		// browser only exposes a small fixed set of response headers to JS
		// (Content-Type, Content-Length, a few others) -- any custom header,
		// including this one, is invisible to axios/fetch entirely unless
		// explicitly listed here. curl/Postman/a Node script never enforce
		// this (it's a browser-only restriction), which is exactly why this
		// was invisible to every check run against this backend until now.
		header('Access-Control-Expose-Headers: ' . config('App')->CSRFHeaderName);
		header('Access-Control-Max-Age: 3600');
	}

	if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS')
	{
		http_response_code(204);
		exit;
	}
});

Events::on('pre_system', function () {
	if (ENVIRONMENT !== 'testing')
	{
		if (ini_get('zlib.output_compression'))
		{
			throw FrameworkException::forEnabledZlibOutputCompression();
		}

		while (ob_get_level() > 0)
		{
			ob_end_flush();
		}

		ob_start(function ($buffer) {
			return $buffer;
		});
	}

	/*
	 * --------------------------------------------------------------------
	 * Debug Toolbar Listeners.
	 * --------------------------------------------------------------------
	 * If you delete, they will no longer be collected.
	 */
	if (CI_DEBUG)
	{
		Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
		Services::toolbar()->respond();
	}
});
