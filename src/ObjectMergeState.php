<?php

namespace DCarbone;

/*
 * Copyright 2020 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Class ObjectMergeState
 * @package DCarbone
 */
class ObjectMergeState
{
    /**
     * Whether this is part of a recursive merge or not
     *
     * @var bool
     */
    public $recursive;

    /**
     * Specified merge options
     *
     * @var int
     */
    public $opts;

    /**
     * Current depth of merge
     *
     * @var int
     */
    public $depth;

    /**
     * Full context, from root, of the current value merge
     *
     * @var array
     */
    public $context;

    /**
     * Current key being merged
     *
     * @var int|string
     */
    public $key;

    /**
     * The left side value being merged
     *
     * @var mixed
     */
    public $leftValue;

    /**
     * The right side value being merged
     *
     * @var mixed
     */
    public $rightValue;

    /**
     * @return bool
     */
    public function isRecursive()
    {
        return $this->recursive;
    }

    /**
     * @return int
     */
    public function getOpts()
    {
        return $this->opts;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return int|string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getLeftValue()
    {
        return $this->leftValue;
    }

    /**
     * @return mixed
     */
    public function getRightValue()
    {
        return $this->rightValue;
    }
}