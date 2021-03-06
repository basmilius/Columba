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

namespace Columba\OAuth2\Router\Middleware;

use Columba\OAuth2\Exception\InsufficientClientScopeException;
use Columba\OAuth2\Exception\OAuth2Exception;
use Columba\OAuth2\OAuth2;
use Columba\Router\Context;
use Columba\Router\Middleware\AbstractMiddleware;
use Columba\Router\Response\JsonResponse;
use Columba\Router\Route\AbstractRoute;
use Columba\Router\Router;
use function explode;
use function strpos;

/**
 * Class OAuth2Middleware
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\OAuth2\Router\Middleware
 * @since 1.3.0
 */
abstract class OAuth2Middleware extends AbstractMiddleware
{

	private OAuth2 $oAuth2;
	private ?string $authType;
	private bool $isOAuth2Request;
	private int $ownerId;
	private array $scopes;

	/**
	 * OAuth2Middleware constructor.
	 *
	 * @param Router $router
	 * @param OAuth2 $oAuth2
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	public function __construct(Router $router, OAuth2 $oAuth2)
	{
		parent::__construct($router);

		$this->oAuth2 = $oAuth2;

		$this->isOAuth2Request = isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], ' ');
		$this->authType = $this->isOAuth2Request ? explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[0] : null;

		$this->ownerId = 0;
		$this->scopes = [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 *
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
	 */
	public function forContext(AbstractRoute $route, Context $context, bool &$isValid): void
	{
		if (!$isValid)
			return;

		if (!$this->isAuthBearer())
			return;

		try
		{
			[$ownerId, $scopes] = $this->oAuth2->validateResource();
			$this->ownerId = $ownerId;
			$this->scopes = $scopes;

			$this->onOwnerIdAvailable($this->ownerId);

			$options = $route->getOptions();

			if (!isset($options['scope']))
				return;

			$this->validateScope($options['scope']);
		}
		catch (OAuth2Exception $err)
		{
			$context->setResponseCode($err->getResponseCode());
			$context->setResponse(new JsonResponse(false), $err);
		}
	}

	/**
	 * Returns TRUE if auth type is basic.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function isAuthBasic(): bool
	{
		return $this->isOAuth2Request && $this->authType === 'Basic';
	}

	/**
	 * Returns TRUE if auth type is bearer.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function isAuthBearer(): bool
	{
		return $this->isOAuth2Request && $this->authType === 'Bearer';
	}

	/**
	 * Returns TRUE if this is an oAuth2 request.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function isOAuth2Request(): bool
	{
		return $this->isOAuth2Request;
	}

	/**
	 * Returns TRUE if a {@see $scope} is allowed.
	 *
	 * @param string $scope
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function isScopeAllowed(string $scope): bool
	{
		$validInToken = false;

		foreach ($this->scopes as ['scope' => $scp])
			if ($scp === $scope)
				$validInToken = true;

		if (!$validInToken)
			return false;

		return $this->oAuth2->getScopeFactory()->isScopeAllowed($this->ownerId, $scope);
	}

	/**
	 * Validates if {@see $scope} is permitted for the authenticated token.
	 *
	 * @param string $scope
	 *
	 * @throws InsufficientClientScopeException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function validateScope(string $scope): void
	{
		if (!$this->isScopeAllowed($scope))
			throw new InsufficientClientScopeException();
	}

	/**
	 * Invoked when a owner id is available.
	 *
	 * @param int $ownerId
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected function onOwnerIdAvailable(int $ownerId): void
	{
	}

}
