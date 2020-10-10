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

namespace Columba\Database\Model;

use Columba\Database\Error\ModelException;
use Columba\Facade\Arrayable;
use Columba\Facade\ArrayAccessible;
use Columba\Facade\Debuggable;
use Columba\Facade\Jsonable;
use Columba\Facade\ObjectAccessible;
use Serializable;
use stdClass;
use function in_array;
use function serialize;
use function sprintf;
use function unserialize;

/**
 * Class Base
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Database\Model
 * @since 1.6.0
 */
abstract class Base extends stdClass implements Arrayable, Jsonable, Debuggable, Serializable
{

	use ArrayAccessible;
	use ObjectAccessible;

	protected bool $isNew;
	protected array $modified = [];
	private array $data;

	protected static array $immutable = [];
	protected static bool $isImmutable = false;

	/**
	 * Base constructor.
	 *
	 * @param array|null $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected function __construct(?array $data = null)
	{
		$this->data = $data ?? [];
		$this->isNew = $data === null;

		$this->initialize();
	}

	/**
	 * Initializes the model.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected function initialize(): void
	{
		if ($this->isNew)
			return;

		$this->prepare($this->data);
	}

	/**
	 * Sets the model's data.
	 *
	 * @param array $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * Gets a value.
	 *
	 * @param string $column
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function getOriginalValue(string $column)
	{
		return $this->data[$column] ?? null;
	}

	/**
	 * Gets a value.
	 *
	 * @param string $column
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function getValue(string $column)
	{
		return $this->data[$column];
	}

	/**
	 * Returns TRUE if a value exists.
	 *
	 * @param string $column
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function hasValue(string $column): bool
	{
		return isset($this->data[$column]);
	}

	/**
	 * Sets a value.
	 *
	 * @param string $column
	 * @param mixed $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function setValue(string $column, $value): void
	{
		if (static::$isImmutable)
			throw new ModelException(sprintf('The model %s is immutable.', static::class), ModelException::ERR_IMMUTABLE);

		if ($this->isImmutable($column))
			throw new ModelException(sprintf('The column %s on model %s is immutable.', $column, static::class), ModelException::ERR_IMMUTABLE);

		$this->data[$column] = $value;

		if ($this->hasColumn($column))
			$this->modified[] = $column;
	}

	/**
	 * Unsets a value.
	 *
	 * @param string $column
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function unsetValue(string $column): void
	{
		if (static::$isImmutable)
			throw new ModelException(sprintf('The model %s is immutable.', static::class), ModelException::ERR_IMMUTABLE);

		if ($this->isImmutable($column))
			throw new ModelException(sprintf('The column %s on model %s is immutable.', $column, static::class), ModelException::ERR_IMMUTABLE);

		$this->data[$column] = null;

		if ($this->hasColumn($column))
			$this->modified[] = $column;
	}

	/**
	 * Returns TRUE if the given column is immutable.
	 *
	 * @param string $column
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	private function isImmutable(string $column): bool
	{
		return static::$isImmutable || in_array($column, static::$immutable);
	}

	/**
	 * Returns TRUE if the given column exists in the table linked to this {@see Model}.
	 *
	 * @param string $column
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public abstract function hasColumn(string $column): bool;

	/**
	 * Prepares the data before the model can be used.
	 *
	 * @param array $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected abstract function prepare(array &$data): void;

	/**
	 * Publishes the data to a public something.
	 *
	 * @param array $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected abstract function publish(array &$data): void;

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function serialize(): string
	{
		return serialize($this->data);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function unserialize($serialized): void
	{
		$this->data = unserialize($serialized);
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function jsonSerialize(): array
	{
		$data = $this->data;
		$this->publish($data);

		foreach ($data as &$field)
			if ($field instanceof Jsonable)
				$field = $field->jsonSerialize();

		return $data;
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function toArray(): array
	{
		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function __debugInfo(): array
	{
		$data = [
			'_meta' => [
				'type' => static::class,
				'immutable' => static::$isImmutable,
				'immutable_columns' => static::$immutable,
				'is_new' => $this->isNew
			]
		];
		$data = array_merge($data, $this->data);
		$this->publish($data);

		return $data;
	}

}
