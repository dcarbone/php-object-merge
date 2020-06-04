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
            'objects'  => ['{"key":"value"}', '{"key2":"value2"}'],
            'expected' => '{"key":"value","key2":"value2"}',
        ],
        [
            'objects'  => ['{"key":"value"}', '{"key2":"value2"}', '{"key3":"value3"}'],
            'expected' => '{"key":"value","key2":"value2","key3":"value3"}',
        ],
        [
            'objects'  => ['{"key":"value"}', '{"key2":"value2"}'],
            'expected' => '{"key":"value","key2":"value2"}',
        ],
        [
            'objects'  => ['{"key":["one"]}', '{"key":["two"]}'],
            'expected' => '{"key":["two"]}',
        ],
        [
            'objects'  => ['{"key":' . PHP_INT_MAX . '}', '{"key":true}', '{"key":"not a number"}'],
            'expected' => '{"key":"not a number"}'
        ],
        // todo: figure out how to test exceptions without doing a bunch of work
        //        [
        //            'objects'   => ['{"key":' . PHP_INT_MAX . '}','{"key":true}', '{"key":"not a number"}'],
        //            'expected' => '{"key":"not a number"}',
        //            'opts'     => OBJECT_MERGE_OPT_CONFLICT_EXCEPTION
        //        ],
        [
            'objects'  => ['{"key":["one"]}', '{"key":["two"]}', '{"key":["three"]}'],
            'expected' => '{"key":["one","two","three"]}',
            'recurse'  => true,
        ],
        [
            'objects'  => ['{"key":1}', '{"key":"1"}'],
            'expected' => '{"key":"1"}',
            'recurse'  => true,
        ],
        [
            'objects'  => ['{"key":{"subkey":"subvalue"}}', '{"key":{"subkey2":"subvalue2"}}'],
            'expected' => '{"key":{"subkey":"subvalue","subkey2":"subvalue2"}}',
            'recurse'  => true,
        ],
        [
            'objects'  => ['{"key":1}', '{"key":"1","key2":1}'],
            'expected' => '{"key":"1","key2":1}',
        ],
        [
            'objects'  => ['{"key":["one"]}', '{"key":["one"]}', '{"key":["one"]}'],
            'expected' => '{"key":["one","one","one"]}',
            'recurse'  => true,
        ],
        [
            'objects'  => ['{"key":["one"]}', '{"key":["one","two"]}', '{"key":["one","two","three"]}'],
            'expected' => '{"key":["one","two","three"]}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_UNIQUE_ARRAYS,
        ],
        [
            'objects'  => ['{"key":{}}', '{"key":{}}'],
            'expected' => '{"key":{}}',
        ],
        [
            'objects'  => ['{"key":{"nope":"should not be here"}}', '{"key":{}}'],
            'expected' => '{"key":{}}',
        ],
        [
            'objects'  => ['{"key":{"yep":"i should be here"}}', '{"key":{}}'],
            'expected' => '{"key":{"yep":"i should be here"}}',
            'recurse'  => true,
        ],
        [
            'objects'  => [
                '{"key":{"sub":{"sub2":{"sub3":"value"}}}}',
                '{"key":{"sub":{"sub22":{"sub223":"value2"}}}}'
            ],
            'expected' => '{"key":{"sub":{"sub2":{"sub3":"value"},"sub22":{"sub223":"value2"}}}}',
            'recurse'  => true
        ]
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
            $objects = [];
            foreach ($test['objects'] as $object) {
                $objects[] = $this->doDecode($i, $object);
            }
            $expected = $this->doDecode($i, $test['expected']);
            if (isset($test['recurse']) && $test['recurse']) {
                if (isset($test['opts'])) {
                    $actual = ObjectMerge::mergeRecursiveOpts($test['opts'], ...$objects);
                } else {
                    $actual = ObjectMerge::mergeRecursive(...$objects);
                }
            } elseif (isset($test['opts'])) {
                $actual = ObjectMerge::mergeOpts($test['opts'], ...$objects);
            } else {
                $actual = ObjectMerge::merge(...$objects);
            }
            $this->assertEquals($expected, $actual);
        }
    }
}