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

namespace Columba\Facade;

/**
 * Interface Settable
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Facade
 * @since 1.6.0
 */
interface Settable
{

	/**
	 * Sets a property that doesn't really exist on the current object.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function __set(string $name, $value): void;

}
