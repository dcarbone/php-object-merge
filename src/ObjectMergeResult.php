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

use BadMethodCallException;

/**
 * Class ObjectMergeResult
 * @package DCarbone
 */
class ObjectMergeResult implements \ArrayAccess
{
    /** @var mixed */
    public $value;
    /** @var bool */
    public $continue;

    /**
     * CallbackResult constructor.
     * @param $value
     * @param $continue
     */
    public function __construct($value, $continue)
    {
        $this->value = $value;
        $this->continue = (bool)$continue;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function shouldContinue()
    {
        return $this->continue;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return 0 === $offset || 1 === $offset;
    }

    /**
     * @param int $offset
     * @return mixed|bool
     */
    public function offsetGet($offset)
    {
        if (0 === $offset) {
            return $this->value;
        } elseif (1 === $offset) {
            return $this->value;
        } else {
            throw new \OutOfRangeException(sprintf('Offset %s does not exist on this object', $offset));
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Cannot set values on ' . __CLASS__ . ' using array notation');
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Cannot unset values on ' . __CLASS__ . ' using array notation');
    }
}