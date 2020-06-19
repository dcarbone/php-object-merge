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

    /** @var bool */
    private static $_recursive;
    /** @var int */
    private static $_opts;
    /** @var callable */
    private static $_cb;

    /** @var int */
    private static $_depth = -1;
    /** @var array */
    private static $_context = [];

    // list of types considered "simple"
    private static $_SIMPLE_TYPES = [
        self::NULL_T,
        self::RESOURCE_T,
        self::STRING_T,
        self::BOOLEAN_T,
        self::INTEGER_T,
        self::DOUBLE_T
    ];

    /**
     * @param stdClass ...$objects
     * @return stdClass|null
     */
    public function __invoke(stdClass ...$objects)
    {
        return self::_doMerge(false, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function merge(stdClass ...$objects)
    {
        return self::_doMerge(false, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursive(stdClass ...$objects)
    {
        return self::_doMerge(true, self::DEFAULT_OPTS, null, $objects);
    }

    /**
     * @param $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeOpts($opts, stdClass ...$objects)
    {
        return self::_doMerge(false, $opts, null, $objects);
    }

    /**
     * @param int $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursiveOpts($opts, stdClass ...$objects)
    {
        return self::_doMerge(true, $opts, null, $objects);
    }

    /**
     * @param int $opts
     * @param callable $cb
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeCallback($opts, $cb, stdClass ...$objects)
    {
        return self::_doMerge(false, $opts, $cb, $objects);
    }

    /**
     * @param int $opts
     * @param callable $cb
     * @param stdClass ...$objects
     * @return stdClass
     */
    public static function mergeRecursiveCallback($opts, $cb, stdClass ...$objects)
    {
        return self::_doMerge(true, $opts, $cb, $objects);
    }

    /**
     * @return ObjectMergeState
     */
    public static function partialState()
    {
        return self::_partialState();
    }

    /**
     * @return ObjectMergeState
     */
    private static function _partialState()
    {
        $state = new ObjectMergeState();

        $state->recursive = self::$_recursive;
        $state->opts = self::$_opts;
        $state->depth = self::$_depth;
        $state->context = self::$_context;

        return $state;
    }

    /**
     * @param string|int $key
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @return ObjectMergeState
     */
    private static function _fullState($key, $leftValue, $rightValue)
    {
        $state = self::_partialState();

        $state->key = $key;
        $state->leftValue = $leftValue;
        $state->rightValue = $rightValue;

        return $state;
    }

    private static function _down()
    {
        self::$_depth++;
    }

    private static function _up()
    {
        self::$_depth--;
        self::$_context = array_slice(self::$_context, 0, self::$_depth, true);
    }

    /**
     * @param string $prefix
     * @return string
     */
    private static function _exceptionMessage($prefix)
    {
        return sprintf(
            '%s - $recursive=%s; $opts=%d; $depth=%d; $context=%s',
            $prefix,
            self::$_recursive,
            self::$_opts,
            self::$_depth,
            implode('->', self::$_context)
        );
    }

    /**
     * @param int $opt
     * @return bool
     */
    private static function _optSet($opt)
    {
        return 0 !== (self::$_opts & $opt);
    }

    /**
     * @param mixed $in
     * @return array|bool|float|int|stdClass|string|null
     */
    private static function _newEmptyValue($in)
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
            throw new UnexpectedValueException(self::_exceptionMessage("Unknown value type provided: {$inT}"));
        }
    }

    /**
     * @param mixed $left
     * @param mixed $right
     * @return array
     */
    private static function _compareTypes($left, $right)
    {
        $leftType = gettype($left);
        $rightType = gettype($right);
        return [
            $leftType,
            $rightType,
            ($leftType === $rightType)
            || (self::NULL_T === $leftType && self::RESOURCE_T === $rightType)
            || (self::RESOURCE_T === $leftType && self::NULL_T === $rightType)
        ];
    }

    /**
     * @param array $leftValue
     * @param array $rightValue
     * @return array
     */
    private static function _mergeArrayValues(array $leftValue, array $rightValue)
    {
        self::_down();

        if (self::_optSet(OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES)) {
            $out = [];

            $lc = count($leftValue);
            $rc = count($rightValue);
            $limit = $lc > $rc ? $lc : $rc;

            for ($i = 0; $i < $limit; $i++) {
                $leftDefined = array_key_exists($i, $leftValue);
                $rightDefined = array_key_exists($i, $rightValue);
                $out[$i] = self::_mergeValues(
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
                    $v = self::_mergeObjectValues(new stdClass(), $v);
                } elseif (self::ARRAY_T === $vt) {
                    $v = self::_mergeArrayValues([], $v);
                }
            }
        }

        if (self::_optSet(OBJECT_MERGE_OPT_UNIQUE_ARRAYS)) {
            $out = array_values(array_unique($out, SORT_REGULAR));
        }

        self::_up();

        return $out;
    }

    /**
     * @param stdClass $leftValue
     * @param stdClass $rightValue
     * @return stdClass
     */
    private static function _mergeObjectValues(stdClass $leftValue, stdClass $rightValue)
    {
        self::_down();

        $out = new stdClass();

        foreach (array_keys(get_object_vars($leftValue) + get_object_vars($rightValue)) as $key) {
            $out->{$key} = self::_mergeValues(
                $key,
                property_exists($leftValue, $key) ? $leftValue->{$key} : OBJECT_MERGE_UNDEFINED,
                property_exists($rightValue, $key) ? $rightValue->{$key} : OBJECT_MERGE_UNDEFINED
            );
        }

        self::_up();

        return $out;
    }

    /**
     * @param string|int $key
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @return array|stdClass
     */
    private static function _mergeValues($key, $leftValue, $rightValue)
    {
        self::$_context[self::$_depth] = $key;

        if (isset(self::$_cb)) {
            $res = call_user_func(self::$_cb, self::_fullState($key, $leftValue, $rightValue));
            $resT = gettype($res);
            if (self::OBJECT_T === $resT) {
                if ($res instanceof ObjectMergeResult && !$res->shouldContinue()) {
                    $finalValue = $res->getFinalValue();
                    if (OBJECT_MERGE_UNDEFINED !== $finalValue) {
                        return $finalValue;
                    }
                    $leftValue = $res->getLeftValue();
                    $rightValue = $res->getRightValue();
                }
            } else {
                return $res;
            }
        }

        $leftUndefined = object_merge_value_undefined($leftValue, self::$_opts);
        $rightUndefined = object_merge_value_undefined($rightValue, self::$_opts);

        if ($leftUndefined && $rightUndefined) {
            if (self::_optSet(OBJECT_MERGE_OPT_NULL_AS_UNDEFINED)) {
                return null;
            }
            throw new LogicException(self::_exceptionMessage('Both left and right values are "undefined"'));
        }

        // if the right value was undefined, return left value and move on.
        if ($rightUndefined) {
            return $leftValue;
        }

        // if left side undefined, create new empty representation of the right type to allow processing to continue
        // todo: revisit this, bit wasteful.
        if ($leftUndefined) {
            $leftValue = self::_newEmptyValue($rightValue);
        }

        list($leftType, $rightType, $equalTypes) = self::_compareTypes($leftValue, $rightValue);

        if (!$equalTypes) {
            if (self::_optSet(OBJECT_MERGE_OPT_CONFLICT_EXCEPTION)) {
                throw new UnexpectedValueException(
                    self::_exceptionMessage(
                        sprintf(
                            'Field "%s" has type "%s" on incoming object, but has type "%s" on the root object',
                            $key,
                            $rightType,
                            $leftType
                        )
                    )
                );
            }
            // todo: revisit this, inefficient.
            return self::_mergeValues($key, self::_newEmptyValue($rightValue), $rightValue);
        }

        if (!self::$_recursive || in_array($leftType, self::$_SIMPLE_TYPES, true)) {
            return $rightValue;
        }

        if (self::ARRAY_T === $leftType) {
            return self::_mergeArrayValues($leftValue, $rightValue);
        }

        return self::_mergeObjectValues($leftValue, $rightValue);
    }

    /**
     * @param bool $recursive
     * @param int $opts
     * @param callable|null $cb
     * @param array $objects
     * @return mixed|null
     */
    private static function _doMerge($recursive, $opts, $cb, array $objects)
    {
        if ([] === $objects) {
            return null;
        }

        $root = null;

        // set state
        self::$_recursive = $recursive;
        self::$_opts = $opts;
        self::$_cb = $cb;

        foreach ($objects as $object) {
            if (null === $object) {
                continue;
            }

            if (null === $root) {
                $root = clone $object;
                continue;
            }

            $root = self::_mergeObjectValues($root, !$recursive ? clone $object : $object);
        }

        // reset state
        self::$_depth = -1;
        self::$_context = [];
        self::$_recursive = false;
        self::$_opts = 0;
        self::$_cb = null;

        return $root;
    }
}
