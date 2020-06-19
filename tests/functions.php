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

use DCarbone\ObjectMergeResult;
use DCarbone\ObjectMergeState;

if (!function_exists('merge_int_to_null')) {
    /**
     * @param ObjectMergeState $state
     * @return ObjectMergeResult|null
     */
    function merge_int_to_null(ObjectMergeState $state)
    {
        if (is_int($state->leftValue)) {
            return null;
        }
        return new ObjectMergeResult(true);
    }
}
if (!function_exists('merge_always_continue')) {
    /**
     * @return ObjectMergeResult
     */
    function merge_always_continue()
    {
        return new ObjectMergeResult(true);
    }
}
if (!function_exists('merge_use_left_side')) {
    /**
     * @param ObjectMergeState $state
     * @return mixed
     */
    function merge_use_left_side(ObjectMergeState $state)
    {
        return $state->leftValue;
    }
}