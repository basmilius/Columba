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

namespace Columba\Foundation;

use function php_sapi_name;

/**
 * Class System
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Foundation
 * @since 1.6.0
 */
final class System
{

	/**
	 * Returns TRUE if PHP is running on the built-in webserver.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public static function isBuiltInDevServer(): bool
	{
		return php_sapi_name() === 'cli-server';
	}

	/**
	 * Returns TRUE if PHP is running on the command line interface.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public static function isCLI(): bool
	{
		return php_sapi_name() === 'cli';
	}

}
