<?php
declare(strict_types=1);

namespace Columba\Router\Response;

use Columba\Router\RouterException;

/**
 * Class HtmlResponse
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Router\Response
 * @since 1.3.0
 */
final class HtmlResponse extends AbstractResponse
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.3.0
	 */
	protected final function respond($value): string
	{
		$this->addHeader('Content-Type: text/html; charset=utf-8');

		if (!is_scalar($value))
			throw new RouterException('Response value needs to be scalar.', RouterException::ERR_INVALID_RESPONSE_VALUE);

		return strval($value);
	}

}
