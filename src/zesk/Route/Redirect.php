<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage redirect
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Route;

use zesk\HTTP;
use zesk\Route;
use zesk\Request;
use zesk\Response;
use zesk\Exception\Redirect as RedirectException;

/**
 * Allow a route which redirects
 *
 * path
 *     redirect=/go-here
 *     temporary=true
 *     message=Not here now, come back later.
 *
 * @author kent
 */
class Redirect extends Route {
	/**
	 * @param Request $request
	 * @return Response
	 * @throws RedirectException
	 */
	protected function internalExecute(Request $request): Response {
		throw new RedirectException($this->option('redirect'), $this->option('message'), $this->optionBool('temporary') ? [
			RedirectException::RESPONSE_STATUS_CODE => HTTP::STATUS_TEMPORARY_REDIRECT,
		] : []);
	}
}
