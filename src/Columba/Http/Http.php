<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Columba package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Columba\Http;

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
	 * Performs a GET request.
	 *
	 * @param string        $url
	 * @param callable|null $manipulator
	 * @param Request|null  $request
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function get (string $url, ?callable $manipulator = null, ?Request &$request = null): Response
	{
		$request = new Request($url, RequestMethod::GET);

		if ($manipulator !== null)
			$manipulator($request);

		return $this->makeRequest($request);
	}

	/**
	 * Performs a POST request.
	 *
	 * @param string        $url
	 * @param string        $body
	 * @param callable|null $manipulator
	 * @param Request|null  $request
	 *
	 * @return Response
	 * @throws HttpException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function post (string $url, string $body, ?callable $manipulator = null, ?Request &$request = null): Response
	{
		$request = new Request($url, RequestMethod::POST);
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
	public final function makeRequest (Request $request): Response
	{
		$handle = curl_init();

		curl_setopt($handle, CURLOPT_BINARYTRANSFER, true);
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

		$response = new Response($request, $handle);

		// TODO (Bas): Some checks on $response.

		return $response;
	}

}
