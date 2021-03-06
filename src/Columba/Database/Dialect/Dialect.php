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

namespace Columba\Database\Dialect;

use Columba\Database\Connection\Connection;
use Columba\Database\Error\DatabaseException;
use Columba\Database\Error\QueryException;
use Columba\Database\Query\Builder\Builder;
use function array_map;
use function explode;
use function implode;
use function sprintf;
use function strpos;

/**
 * Class Dialect
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Database\Dialect
 * @since 1.6.0
 */
class Dialect
{

	public string $columnSeparator = ', ';
	public array $escapers = ['', ''];
	public string $indentation = '  ';

	/**
	 * Escapes the given column.
	 *
	 * @param string $column
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function escapeColumn(string $column): string
	{
		return $this->escapeFields($column);
	}

	/**
	 * Escapes the given field.
	 *
	 * @param string $field
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function escapeField(string $field): string
	{
		if ($field === '*' || strpos($field, $this->escapers[0]) !== false)
			return $field;

		return $this->escapers[0] . $field . $this->escapers[1];
	}

	/**
	 * Escapes the given field with multiple sections.
	 *
	 * @param string $fields
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function escapeFields(string $fields): string
	{
		if (strpos($fields, '(') !== false || strpos($fields, ' ') !== false || strpos($fields, ':=') !== false)
			return $fields;

		if (strpos($fields, '.') === false)
			return $this->escapeField($fields);

		$fields = explode('.', $fields);
		$fields = array_map(fn(string $field): string => $this->escapeField($field), $fields);

		return implode('.', $fields);
	}

	/**
	 * Escapes the given table.
	 *
	 * @param string $table
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function escapeTable(string $table): string
	{
		if (strpos($table, ' ') === false)
			return $this->escapeFields($table);

		$parts = explode(' ', $table, 2);
		$parts[0] = $this->escapeFields($parts[0]);

		return implode(' ', $parts);
	}

	/**
	 * Builds an SELECT FOUND_ROWS() query for the {@see Builder}.
	 *
	 * @param Builder $query
	 *
	 * @return Builder
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @see Connection::foundRows()
	 */
	public function foundRows(Builder $query): Builder
	{
		throw $this->notImplemented(__METHOD__);
	}

	/**
	 * Builds an OPTIMIZE TABLE expression for the {@see Builder}.
	 *
	 * @param Builder $query
	 * @param string[] $tables
	 *
	 * @return Builder
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @see Builder::optimizeTable()
	 */
	public function optimizeTable(Builder $query, array $tables): Builder
	{
		throw $this->notImplemented(__METHOD__);
	}

	/**
	 * Throws a not implemented {@see QueryException}.
	 *
	 * @param string $method
	 *
	 * @return DatabaseException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 * @see QueryException
	 * @see QueryException::ERR_NOT_IMPLEMENTED
	 */
	protected function notImplemented(string $method): DatabaseException
	{
		return new QueryException(sprintf('Method %s is not implemented for %s.', $method, static::class), QueryException::ERR_NOT_IMPLEMENTED);
	}

}
