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

use LogicException;
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
     * @param stdClass ...$objects
     * @return stdClass|null
     */
    public function __invoke(stdClass ...$objects)
    {
        return self::doMerge(false, self::DEFAULT_OPTS, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function merge(stdClass ...$objects)
    {
        return self::doMerge(false, self::DEFAULT_OPTS, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursive(stdClass ...$objects)
    {
        return self::doMerge(true, self::DEFAULT_OPTS, $objects);
    }

    /**
     * @param $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeOpts($opts, stdClass ...$objects)
    {
        return self::doMerge(false, $opts, $objects);
    }

    /**
     * @param int $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursiveOpts($opts, stdClass ...$objects)
    {
        return self::doMerge(true, $opts, $objects);
    }

    /**
     * @param int $opts
     * @param int $opt
     * @return bool
     */
    private static function optSet($opts, $opt)
    {
        return 0 !== ($opts & $opt);
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
     * @param array $leftValue
     * @param array $rightValue
     * @return array
     */
    private static function mergeArrayValues($recurse, $opts, array $leftValue, array $rightValue)
    {
        if (self::optSet($opts, OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES)) {
            $out = [];

            $lc = count($leftValue);
            $rc = count($rightValue);
            $limit = $lc > $rc ? $lc : $rc;

            for ($i = 0; $i < $limit; $i++) {
                $leftDefined = array_key_exists($i, $leftValue);
                $rightDefined = array_key_exists($i, $rightValue);
                $out[$i] = self::mergeValues(
                    $recurse,
                    $opts,
                    $i,
                    $leftDefined ? $leftValue[$i] : OBJECT_MERGE_UNDEFINED,
                    $rightDefined ? $rightValue[$i] : OBJECT_MERGE_UNDEFINED
                );
            }
        } else {
            $out = array_merge($leftValue, $rightValue);

            foreach ($out as $i => &$v) {
                $vt = gettype($v);
                if (self::OBJECT_T === $vt) {
                    $v = self::mergeObjectValues($recurse, $opts, new stdClass(), $v);
                } elseif (self::ARRAY_T === $vt) {
                    $v = self::mergeArrayValues($recurse, $opts, [], $v);
                }
            }
        }

        if (self::optSet($opts, OBJECT_MERGE_OPT_UNIQUE_ARRAYS)) {
            return array_values(array_unique($out, SORT_REGULAR));
        }

        return $out;
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param stdClass $leftValue
     * @param stdClass $rightValue
     * @return stdClass
     */
    private static function mergeObjectValues($recurse, $opts, stdClass $leftValue, stdClass $rightValue)
    {
        $out = new stdClass();

        foreach (array_merge(get_object_vars($leftValue), get_object_vars($rightValue)) as $k => $v) {
            $leftDefined = property_exists($leftValue, $k);
            $rightDefined = property_exists($rightValue, $k);
            $out->{$k} = self::mergeValues(
                $recurse,
                $opts,
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
     * @param string|int $key
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @return array|stdClass
     */
    private static function mergeValues($recurse, $opts, $key, $leftValue, $rightValue)
    {
        $leftUndefined = OBJECT_MERGE_UNDEFINED === $leftValue;
        $rightUndefined = OBJECT_MERGE_UNDEFINED === $rightValue;

        if ($leftUndefined && $rightUndefined) {
            throw new LogicException(
                sprintf(
                    'Both left and right values are "undefined": $recurse=%s; $opts=%d; $key=%s',
                    $recurse ? 'true' : 'false',
                    $opts,
                    $key
                )
            );
        }

        // if the right value was undefined, return left value and move on.
        if ($rightUndefined) {
            return $leftValue;
        }

        // if left side undefined, create new empty representation of the right type to allow processing to continue
        // todo: revisit this, bit wasteful.
        if ($leftUndefined) {
            $leftValue = self::newEmptyValue($rightValue);
        }

        list($leftType, $rightType, $equalTypes) = self::compareTypes($leftValue, $rightValue);

        if (!$equalTypes) {
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
            // todo: revisit this, inefficient.
            return self::mergeValues($recurse, $opts, $key, self::newEmptyValue($rightValue), $rightValue);
        }

        if (!$recurse || in_array($leftType, self::$_SIMPLE_TYPES, true)) {
            return $rightValue;
        }

        if (self::ARRAY_T === $leftType) {
            return self::mergeArrayValues($recurse, $opts, $leftValue, $rightValue);
        }

        return self::mergeObjectValues($recurse, $opts, $leftValue, $rightValue);
    }

    /**
     * @param bool $recurse
     * @param int $opts
     * @param array $objects
     * @return mixed|null
     */
    private static function doMerge($recurse, $opts, array $objects)
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
                $root = clone $object;
                continue;
            }

            $root = self::mergeObjectValues($recurse, $opts, $root, !$recurse ? clone $object : $object);
        }

        return $root;
    }
}
