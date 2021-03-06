<?php
/**
 * Copyright (c) 2019 - 2020 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Columba package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Columba\Http;

use function curl_init;
use function curl_setopt;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTP_VERSION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;

/**
 * Class Http
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Http
 * @since 1.2.0
 */
final class Http
{

	/**
	 * Performs a DELETE request.
	 *
	 * @param string $url
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function delete(string $url, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::DELETE);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a GET request.
	 *
	 * @param string $url
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function get(string $url, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::GET);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a HEAD request.
	 *
	 * @param string $url
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function head(string $url, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::HEAD);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a OPTIONS request.
	 *
	 * @param string $url
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function options(string $url, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::OPTIONS);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a PATCH request.
	 *
	 * @param string $url
	 * @param string $body
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function patch(string $url, string $body, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::PATCH);
		$request->setBody($body);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a POST request.
	 *
	 * @param string $url
	 * @param string $body
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function post(string $url, string $body, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::POST);
		$request->setBody($body);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a PUT request.
	 *
	 * @param string $url
	 * @param string|null $body
	 * @param callable|null $manipulator
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function put(string $url, ?string $body = null, ?callable $manipulator = null): Response
	{
		$request = new Request($url, RequestMethod::PUT);

		if ($body !== null)
			$request->setBody($body);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs the request.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function makeRequest(Request $request): Response
	{
		$handle = curl_init();

		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $request->getRequestMethod());
		curl_setopt($handle, CURLOPT_ENCODING, 'gzip');
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_HTTPHEADER, HttpUtil::parseArrayOfHeaders($request->getHeaders()));
		curl_setopt($handle, CURLOPT_HTTP_VERSION, 3); // HTTP/2
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_URL, $request->getRequestUrl());
		curl_setopt($handle, CURLOPT_USERAGENT, $request->getUserAgent());

		if ($request->getBody() !== null)
			curl_setopt($handle, CURLOPT_POSTFIELDS, $request->getBody());

		foreach ($request->getOptions() as $option => $value)
			curl_setopt($handle, $option, $value);

		return new Response($request, $handle);
	}

}
