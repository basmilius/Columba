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

namespace Columba\Database\Model\Relation;

use Columba\Database\Model\Model;
use Columba\Database\Query\Builder\Builder;

/**
 * Class One
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Database\Model\Relation
 * @since 1.6.0
 */
class One extends Relation
{

	private string $referenceKey;
	private string $selfKey;

	/**
	 * One constructor.
	 *
	 * @param Model|string $referencedModel
	 * @param string|null  $selfKey
	 * @param string|null  $referenceKey
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function __construct(string $referencedModel, ?string $selfKey = null, ?string $referenceKey = null)
	{
		parent::__construct($referencedModel);

		$this->referenceKey = $referenceKey ?? $referencedModel::column('id');
		$this->selfKey = $selfKey ?? $referencedModel::table() . '_id';
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function get(): ?Model
	{
		if ($this->referencedModel::column($this->referencedModel::primaryKey()) === $this->referenceKey)
		{
			$cache = $this->referencedModel::connection()->getCache();
			$key = $this->model->getValue($this->selfKey);

			if ($cache->has($key, $this->referencedModel))
				return $cache->get($key, $this->referencedModel);
		}

		return $this->collection()->first();
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function relevantColumns(): array
	{
		return [$this->selfKey];
	}

	/**
	 * {@inheritDoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected function buildBaseQuery(): Builder
	{
		return $this->where($this->referenceKey, $this->model->getValue($this->selfKey) ?? 0);
	}

}
