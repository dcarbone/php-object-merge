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

use stdClass;
use UnexpectedValueException;

/**
 * Class ObjectMerge
 * @package DCarbone
 */
class ObjectMerge
{
    const DEFAULT_OPTS = OBJECT_MERGE_OPT_CONFLICT_OVERWRITE;

    // simple types that require no merge logic
    const NULL_T     = 'NULL';
    const RESOURCE_T = 'resource';
    const STRING_T   = 'string';
    const BOOLEAN_T  = 'boolean';
    const INTEGER_T  = 'integer';
    const DOUBLE_T   = 'double';

    // complicated types
    const ARRAY_T  = 'array';
    const OBJECT_T = 'object';

    // list of types considered "simple"
    private static $_SIMPLE_TYPES = array(
        self::NULL_T,
        self::RESOURCE_T,
        self::STRING_T,
        self::BOOLEAN_T,
        self::INTEGER_T,
        self::DOUBLE_T
    );

    /**
     * @param stdClass $root
     * @param stdClass ...$others
     * @return stdClass
     */
    public function __invoke(stdClass $root, stdClass ...$others)
    {
        return self::doMerge(false, $root, self::DEFAULT_OPTS, $others);
    }

    /**
     * @param stdClass $root
     * @param $opts
     * @param stdClass ...$others
     * @return stdClass
     */
    public static function mergeOpts(stdClass $root, $opts, stdClass ...$others)
    {
        return self::doMerge(false, $root, $opts, $others);
    }

    /**
     * @param stdClass $root
     * @param stdClass ...$others
     * @return stdClass
     */
    public static function merge(stdClass $root, stdClass ...$others)
    {
        return self::doMerge(false, $root, self::DEFAULT_OPTS, $others);
    }

    /**
     * @param stdClass $root
     * @param int $opts
     * @param stdClass ...$others
     * @return stdClass
     */
    public static function mergeRecursiveOpts(stdClass $root, $opts, stdClass ...$others)
    {
        return self::doMerge(true, $root, $opts, $others);
    }

    /**
     * @param stdClass $root
     * @param stdClass ...$others
     * @return stdClass
     */
    public static function mergeRecursive(stdClass $root, stdClass ...$others)
    {
        return self::doMerge(true, $root, self::DEFAULT_OPTS, $others);
    }

    /**
     * @param int $opts
     * @param int $opt
     * @return bool
     */
    private static function optSet($opts, $opt)
    {
        return $opt === ($opts & (1 << ($opt - 1)));
    }

    /**
     * @param array $orig
     * @param array $inc
     * @param int $opts
     * @return array
     */
    private static function mergeArrayValues(array $orig, array $inc, $opts)
    {
        $out = $orig;
        foreach ($inc as $v) {
            $out[] = $v;
        }
        if (self::optSet($opts, OBJECT_MERGE_OPT_UNIQUE_ARRAYS)) {
            return array_values(array_unique($out));
        }
        return $out;
    }

    /**
     * @param bool $recurse
     * @param stdClass $orig
     * @param stdClass $inc
     * @param int $opts
     * @return stdClass
     */
    private static function mergeObjectValues($recurse, stdClass $orig, stdClass $inc, $opts)
    {
        $out = $orig;
        foreach (get_object_vars($inc) as $k => $v) {
            if (!isset($out->{$k})) {
                $out->{$k} = $v;
            } else {
                $out->{$k} = self::mergeValues($recurse, $k, $out->{$k}, $v, $opts);
            }
        }
        return $out;
    }

    /**
     * @param bool $recurse
     * @param string|int $key
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @param int $opts
     * @return mixed
     */
    private static function mergeValues($recurse, $key, $leftValue, $rightValue, $opts)
    {
        $leftType = gettype($leftValue);
        $rightType = gettype($rightValue);

        if ($leftType !== $rightType) {
            if (self::optSet($opts, OBJECT_MERGE_OPT_CONFLICT_EXCEPTION)) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Field "%s" has type "%s" on incoming object, but has type "%s" on the root object',
                        $key,
                        $rightType,
                        $leftType
                    )
                );
            }
            // clone to avoid side-effects
            if (self::OBJECT_T === $rightType) {
                return clone $rightValue;
            }
            return $rightValue;
        }

        if (!$recurse || in_array($leftType, self::$_SIMPLE_TYPES)) {
            return $rightValue;
        }

        if (self::ARRAY_T === $leftType) {
            return self::mergeArrayValues($leftValue, $rightValue, $opts);
        }

        return self::mergeObjectValues($recurse, $leftValue, $rightValue, $opts);
    }

    /**
     * @param bool $recurse
     * @param stdClass $root
     * @param int $opts
     * @param stdClass[] $others
     * @return stdClass
     */
    private static function doMerge($recurse, stdClass $root, $opts, array $others)
    {
        if (0 === count($others)) {
            return $root;
        }

        $new = clone $root;

        foreach ($others as $other) {
            if (null === $other) {
                continue;
            }

            foreach (get_object_vars($other) as $k => $v) {
                if (!isset($new->{$k})) {
                    $new->{$k} = $v;
                } else {
                    $new->{$k} = self::mergeValues($recurse, $k, $new->{$k}, $v, $opts);
                }
            }
        }

        return $new;
    }
}
