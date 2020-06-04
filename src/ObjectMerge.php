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

    /** @var mixed */
    private static $_leftContext;
    /** @var mixed */
    private static $_rightContext;

    /**
     * @param stdClass ...$objects
     * @return stdClass|null
     */
    public function __invoke(stdClass ...$objects)
    {
        return self::doMerge(false, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function merge(stdClass ...$objects)
    {
        return self::doMerge(false, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursive(stdClass ...$objects)
    {
        return self::doMerge(true, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeOpts($opts, stdClass ...$objects)
    {
        return self::doMerge(false, $opts, null, $objects);
    }

    /**
     * @param int $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursiveOpts($opts, stdClass ...$objects)
    {
        return self::doMerge(true, $opts, null, $objects);
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
     * @param mixed $in
     * @return array|bool|float|int|stdClass|string|null
     */
    private static function newEmptyValue($in)
    {
        $inT = gettype($in);
        if (self::STRING_T === $inT) {
            return '';
        } elseif (self::INTEGER_T === $inT) {
            return 0;
        } elseif (self::DOUBLE_T === $inT) {
            return 0.0;
        } elseif (self::BOOLEAN_T === $inT) {
            return false;
        } elseif (self::ARRAY_T === $inT) {
            return [];
        } elseif (self::OBJECT_T === $inT) {
            return new stdClass();
        } elseif (self::RESOURCE_T === $inT || self::NULL_T === $inT) {
            return null;
        } else {
            throw new UnexpectedValueException(sprintf('Unknown value type provided: %s', $inT));
        }
    }

    /**
     * @param mixed $left
     * @param mixed $right
     * @return array
     */
    private static function compareTypes($left, $right)
    {
        $leftType = gettype($left);
        $rightType = gettype($right);
        return [
            $leftType,
            $rightType,
            ($leftType === $rightType) || (self::NULL_T === $leftType && self::RESOURCE_T === $rightType) || (self::RESOURCE_T === $leftType && self::NULL_T === $rightType)
        ];
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param callable $cb
     * @param mixed $key
     * @param array $leftValue
     * @param array $rightValue
     * @return array
     */
    private static function mergeArrayValues($recurse, $opts, $cb, $key, array $leftValue, array $rightValue)
    {
        $out = array_merge($leftValue, $rightValue);

        foreach ($out as $i => &$v) {
            $vt = gettype($v);
            if (self::OBJECT_T === $vt) {
                $v = self::mergeObjectValues($recurse, $opts, $cb, $i, new stdClass(), $v);
            } elseif (self::ARRAY_T === $vt) {
                $v = self::mergeArrayValues($recurse, $opts, $cb, $i, [], $v);
            }
        }

        if (self::optSet($opts, OBJECT_MERGE_OPT_UNIQUE_ARRAYS)) {
            return array_values(array_unique($out));
        }

        return $out;
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param callable $cb
     * @param stdClass $leftValue
     * @param stdClass $rightValue
     * @return stdClass
     */
    private static function mergeObjectValues($recurse, $opts, $cb, $key, stdClass $leftValue, stdClass $rightValue)
    {
        $out = new stdClass();
        foreach (array_merge(get_object_vars($leftValue), get_object_vars($rightValue)) as $k => $v) {
            $leftDefined = isset($leftValue->{$k});
            $rightDefined = isset($rightValue->{$k});
            $out->{$k} = self::mergeValues(
                $recurse,
                $opts,
                $cb,
                $k,
                $leftDefined ? $leftValue->{$k} : OBJECT_MERGE_UNDEFINED,
                $rightDefined ? $rightValue->{$k} : OBJECT_MERGE_UNDEFINED
            );
        }
        return $out;
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param callable $cb
     * @param string|int $key
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @return mixed
     */
    private static function mergeValues($recurse, $opts, $cb, $key, $leftValue, $rightValue)
    {
        $leftUndefined = OBJECT_MERGE_UNDEFINED === $leftValue;
        $rightUndefined = OBJECT_MERGE_UNDEFINED === $rightValue;

        if ($leftUndefined && $rightUndefined) {
            throw new \LogicException(
                sprintf(
                    'Both left and right values are "undefined": $recurse=%s; $opts=%d; $key=%s',
                    $recurse ? 'true' : 'false',
                    $opts,
                    $key
                )
            );
        } elseif ($leftUndefined) {
            $leftValue = self::newEmptyValue($rightValue);
        } elseif ($rightUndefined) {
            $rightValue = self::newEmptyValue($leftValue);
        }

        list($leftType, $rightType, $equal) = self::compareTypes($leftValue, $rightValue);

        if (!$equal && self::optSet($opts, OBJECT_MERGE_OPT_CONFLICT_EXCEPTION)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Field "%s" has type "%s" on incoming object, but has type "%s" on the root object',
                    $key,
                    $rightType,
                    $leftType
                )
            );
        }

        if ($rightUndefined) {
            return $leftValue;
        }

        if (!$recurse || in_array($leftType, self::$_SIMPLE_TYPES, true)) {
            return $rightValue;
        }

        self::$_leftContext = $leftValue;
        self::$_rightContext = $rightValue;

        if (self::ARRAY_T === $leftType) {
            return self::mergeArrayValues($recurse, $opts, $cb, $key, $leftValue, $rightValue);
        }

        return self::mergeObjectValues($recurse, $opts, $cb, $key, $leftValue, $rightValue);
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param callable $cb
     * @param array $objects
     * @return mixed|null
     */
    private static function doMerge($recurse, $opts, $cb, array $objects)
    {
        if ([] === $objects) {
            return null;
        }

        $root = null;

        foreach ($objects as $object) {
            if (null === $object) {
                continue;
            }

            if (null === $root) {
                $root = self::$_leftContext = clone $object;
                continue;
            }

            self::$_rightContext = $object;

            $root = self::mergeObjectValues($recurse, $opts, $cb, null, $root, $object);
        }

        self::$_leftContext = null;
        self::$_rightContext = null;

        return $root;
    }
}
