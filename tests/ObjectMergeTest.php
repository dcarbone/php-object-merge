<?php

namespace DCarbone\Tests;

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
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * Note: tests are only run under php 7.4+
 *
 * Class ObjectMergeTest
 */
class ObjectMergeTest extends TestCase
{
    private static $_tests = array(
        [
            'root'     => '{"key":"value"}',
            'others'   => ['{"key2":"value2"}'],
            'expected' => '{"key":"value","key2":"value2"}',
        ],
        [
            'root'     => '{"key":"value"}',
            'others'   => ['{"key2":"value2"}', '{"key3":"value3"}'],
            'expected' => '{"key":"value","key2":"value2","key3":"value3"}',
        ],
        [
            'root'     => '{"key":"value"}',
            'others'   => ['{"key2":"value2"}'],
            'expected' => '{"key":"value","key2":"value2"}',
        ],
        [
            'root'     => '{"key":["one"]}',
            'others'   => ['{"key":["two"]}'],
            'expected' => '{"key":["two"]}',
        ],
        [
            'root'     => '{"key":' . PHP_INT_MAX . '}',
            'others'   => ['{"key":true}', '{"key":"not a number"}'],
            'expected' => '{"key":"not a number"}'
        ],
        // todo: figure out how to test exceptions without doing a bunch of work
        //        [
        //            'root'     => '{"key":' . PHP_INT_MAX . '}',
        //            'others'   => ['{"key":true}', '{"key":"not a number"}'],
        //            'expected' => '{"key":"not a number"}',
        //            'opts'     => OBJECT_MERGE_OPT_CONFLICT_EXCEPTION
        //        ],
        [
            'root'     => '{"key":["one"]}',
            'others'   => ['{"key":["two"]}', '{"key":["three"]}'],
            'expected' => '{"key":["one","two","three"]}',
            'recurse'  => true,
        ],
        [
            'root'     => '{"key":1}',
            'others'   => ['{"key":"1"}'],
            'expected' => '{"key":"1"}',
            'recurse'  => true,
        ],
        [
            'root'     => '{"key":{"subkey":"subvalue"}}',
            'others'   => ['{"key":{"subkey2":"subvalue2"}}'],
            'expected' => '{"key":{"subkey":"subvalue","subkey2":"subvalue2"}}',
            'recurse'  => true,
        ],
        [
            'root'     => '{"key":1}',
            'others'   => ['{"key":"1","key2":1}'],
            'expected' => '{"key":"1","key2":1}',
        ],
        [
            'root'     => '{"key":["one"]}',
            'others'   => ['{"key":["one"]}', '{"key":["one"]}'],
            'expected' => '{"key":["one","one","one"]}',
            'recurse'  => true,
        ],
        [
            'root'     => '{"key":["one"]}',
            'others'   => ['{"key":["one","two"]}', '{"key":["one","two","three"]}'],
            'expected' => '{"key":["one","two","three"]}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_UNIQUE_ARRAYS,
        ],
    );

    /**
     * @param string|int $test
     * @param string $json
     * @return stdClass
     */
    private function doDecode($test, $json)
    {
        $out = json_decode($json);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                sprintf('json_decode returned error while processing test "%s": %s', $test, $json)
            );
        }
        return $out;
    }

    public function testObjectMerge()
    {
        foreach (self::$_tests as $i => $test) {
            $root = $this->doDecode($i, $test['root']);
            $others = [];
            foreach ($test['others'] as $other) {
                $others[] = $this->doDecode($i, $other);
            }
            $expected = $this->doDecode($i, $test['expected']);
            if (isset($test['recurse']) && $test['recurse']) {
                if (isset($test['opts'])) {
                    $actual = ObjectMerge::mergeRecursiveOpts($root, $test['opts'], ...$others);
                } else {
                    $actual = ObjectMerge::mergeRecursive($root, ...$others);
                }
            } elseif (isset($test['opts'])) {
                $actual = ObjectMerge::mergeOpts($root, $test['opts'], ...$others);
            } else {
                $actual = ObjectMerge::merge($root, ...$others);
            }
            $this->assertEquals($expected, $actual);
            var_dump($actual);
        }
    }
}