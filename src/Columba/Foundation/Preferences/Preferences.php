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

namespace Columba\Foundation\Preferences;

use Columba\Facade\Arrayable;
use Columba\Facade\Debuggable;
use Columba\Facade\IsCountable;
use Columba\Facade\Jsonable;
use Columba\Facade\Loopable;
use Columba\Util\ArrayUtil;
use function array_combine;
use function array_keys;
use function array_search;
use function array_values;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_readable;
use function json_decode;
use function json_last_error;
use function sprintf;

/**
 * Class Preferences
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Foundation\Preferences
 * @since 1.0.0
 */
final class Preferences implements Arrayable, Debuggable, IsCountable, Loopable, Jsonable
{

	private ?Preferences $parent;
	private int $position;
	private array $keys;
	private array $values;

	/**
	 * Preferences constructor.
	 *
	 * @param array $data
	 * @param Preferences|null $parent
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function __construct(array $data, ?Preferences $parent = null)
	{
		$this->parent = $parent;
		$this->position = 0;

		$this->keys = array_keys($data);
		$this->values = array_values($data);

		$this->loop();
	}

	/**
	 * Loops the data array for underlying instances.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function loop(): void
	{
		foreach ($this->keys as $index => $key)
		{
			if (!is_array($this->values[$index]) || ArrayUtil::isSequentialArray($this->values[$index]))
				continue;

			$this->values[$index] = new self($this->values[$index], $this);
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function current()
	{
		return $this->values[$this->position];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function next(): void
	{
		++$this->position;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function key(): string
	{
		return $this->keys[$this->position];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function valid(): bool
	{
		return isset($this->keys[$this->position]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function rewind(): void
	{
		$this->position = 0;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function offsetExists($offset): bool
	{
		return in_array($offset, $this->keys);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function offsetGet($offset)
	{
		if ($offset === -1)
			return $this->parent;

		return $this->values[$this->findIndex($this->keys, $offset)] ?? null;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function offsetSet($offset, $value): void
	{
		$index = $this->findIndex($this->keys, $offset);

		if ($index === null)
			throw new PreferencesException(sprintf('Could not set "%s" as it did not exist in preferences file.', $offset));

		$this->values[$index] = $value;
		$this->loop();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function offsetUnset($offset): void
	{
		$index = $this->findIndex($this->keys, $offset);

		if ($index === null)
			return;

		unset($this->values[$index]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.5.0
	 */
	public final function toArray(): array
	{
		return array_combine($this->keys, $this->values);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function count(): int
	{
		return count($this->keys);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function jsonSerialize(): array
	{
		return $this->toArray();
	}

	/**
	 * Finds an index in an array.
	 *
	 * @param array $array
	 * @param mixed $value
	 *
	 * @return int|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.4.0
	 */
	private function findIndex(array $array, $value): ?int
	{
		$index = array_search($value, $array);

		return $index !== false ? $index : null;
	}

	/**
	 * Creates a new {@see Preferences} instance from JSON.
	 *
	 * @param string $fileName
	 *
	 * @return Preferences
	 * @throws PreferencesException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public static function loadFromJson(string $fileName): self
	{
		if (!is_readable($fileName))
			throw new PreferencesException(sprintf('"%s" must be a readable file!', $fileName), PreferencesException::ERR_INVALID_ARGUMENT);

		$data = json_decode(file_get_contents($fileName), true);

		if ($data === null && json_last_error() !== JSON_ERROR_NONE)
			throw new PreferencesException(sprintf('"%s" must be a valid JSON file!', $fileName), PreferencesException::ERR_INVALID_ARGUMENT);

		return new self($data);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function __debugInfo(): array
	{
		return [
			'data' => '** Hidden for security reasons. **'
		];
	}

}
