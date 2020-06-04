<?php

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

use DCarbone\ObjectMerge;

if (!function_exists('object_merge_value_undefined')) {
    /**
     * @param mixed $value
     * @return bool
     */
    function object_merge_value_undefined($value)
    {
        return $value === OBJECT_MERGE_UNDEFINED;
    }
}

if (!function_exists('object_merge')) {
    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    function object_merge(stdClass ...$objects)
    {
        return ObjectMerge::merge($objects);
    }
}
if (!function_exists('object_merge_recursive')) {
    /**
     * @param stdClass ...$objects
     * @return stdClass
     */
    function object_merge_recursive(stdClass ...$objects)
    {
        return ObjectMerge::mergeRecursive($objects);
    }
}
if (!function_exists('object_merge_opts')) {
    /**
     * @param int $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    function object_merge_opts($opts, stdClass ...$objects)
    {
        return ObjectMerge::mergeOpts($opts, $objects);
    }
}
if (!function_exists('object_merge_recursive_opts')) {
    /**
     * @param int $opts
     * @param stdClass ...$objects
     * @return stdClass
     */
    function object_merge_recursive_opts($opts, stdClass ...$objects)
    {
        return ObjectMerge::mergeRecursiveOpts($opts, $objects);
    }
}