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

if (!function_exists('object_merge')) {
    /**
     * @param stdClass $root
     * @param stdClass ...$others
     * @return stdClass
     */
    function object_merge(stdClass $root, stdClass ...$others)
    {
        return ObjectMerge::merge($root, ...$others);
    }
}
if (!function_exists('object_merge_opts')) {
    /**
     * @param stdClass $root
     * @param int $opts
     * @param stdClass ...$others
     * @return stdClass
     */
    function object_merge_opts(stdClass $root, $opts, stdClass ...$others)
    {
        return ObjectMerge::mergeOpts($root, $opts, ...$others);
    }
}
if (!function_exists('object_merge_recursive')) {
    /**
     * @param stdClass $root
     * @param stdClass ...$others
     * @return stdClass
     */
    function object_merge_recursive(stdClass $root, stdClass ...$others)
    {
        return ObjectMerge::mergeRecursive($root, ...$others);
    }
}
if (!function_exists('object_merge_recursive_opts')) {
    /**
     * @param stdClass $root
     * @param int $opts
     * @param stdClass ...$others
     * @return stdClass
     */
    function object_merge_recursive_opts(stdClass $root, $opts, stdClass ...$others)
    {
        return ObjectMerge::mergeRecursiveOpts($root, $opts, ...$others);
    }
}