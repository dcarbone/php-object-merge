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

use ArrayAccess;
use BadMethodCallException;
use OutOfRangeException;

/**
 * Class ObjectMergeResult
 * @package DCarbone
 */
class ObjectMergeResult implements ArrayAccess
{
    /**
     * If true, value merging will proceed ignoring all other values from this object.
     *
     * @var bool
     */
    public $continue;

    /**
     * If this property is set, any recursion will cease along this branch and this value will be used outright.
     *
     * $leftValue and $rightValue values will be entirely ignored.
     *
     * @var mixed
     */
    public $finalValue;

    /**
     * If this property is set, this value will be used as the "left" side of the merge
     *
     * @var mixed
     */
    public $leftValue;

    /**
     * If this property is set, this value will be used as the "right" side of the merge
     *
     * @var mixed
     */
    public $rightValue;

    /**
     * ObjectMergeResult constructor.
     * @param bool $continue
     */
    public function __construct($continue = true)
    {
        $this->continue = $continue;
    }

    /**
     * @return bool
     */
    public function shouldContinue()
    {
        return isset($this->continue) ? boolval($this->continue) : false;
    }

    /**
     * @return mixed
     */
    public function getFinalValue()
    {
        return (isset($this->finalValue) || null === $this->finalValue) ? $this->finalValue : OBJECT_MERGE_UNDEFINED_VALUE;
    }

    /**
     * @return mixed
     */
    public function getLeftValue()
    {
        return (isset($this->leftValue) || null === $this->leftValue) ? $this->leftValue : OBJECT_MERGE_UNDEFINED_VALUE;
    }

    /**
     * @return mixed
     */
    public function getRightValue()
    {
        return (isset($this->rightValue) || null === $this->rightValue) ? $this->rightValue : OBJECT_MERGE_UNDEFINED_VALUE;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return is_int($offset) && 0 <= $offset && $offset <= 3;
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (0 === $offset) {
            return $this->continue;
        } elseif (1 === $offset) {
            return $this->getFinalValue();
        } elseif (2 === $offset) {
            return $this->getLeftValue();
        } elseif (3 === $offset) {
            return $this->getRightValue();
        } else {
            throw new OutOfRangeException(sprintf('Offset %s does not exist', var_export($offset, true)));
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Cannot set values on this type using array notation');
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Cannot unset values on this type using array notation');
    }
}
