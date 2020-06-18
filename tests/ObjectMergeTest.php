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
        ],
        [
            'objects'  => [
                '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML"],"para":"A meta-markup language, used to create markup languages such as DocBook.","leftOnly":["things"]},"GlossSee":"markup","leftOnly":{"leftKey":"leftValue"},"GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","bothSides":{"leftKey":"leftValue"}}},"title":"S","leftOnly":"hello"},"title":"example glossary"}}',
                '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML"],"para":"A meta-markup language, used to create markup languages such as DocBook."},"GlossSee":"markup","GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","rightOnly":{"rightKey":"rightKey"},"bothSides":{"rightKey":"rightValue"}}},"title":"S"},"title":"example glossary","rightOnly":"hello"}}',
            ],
            'expected' => '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML","GML","XML"],"leftOnly":["things"],"para":"A meta-markup language, used to create markup languages such as DocBook."},"GlossSee":"markup","GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","bothSides":{"leftKey":"leftValue","rightKey":"rightValue"},"leftOnly":{"leftKey":"leftValue"},"rightOnly":{"rightKey":"rightKey"}}},"leftOnly":"hello","title":"S"},"rightOnly":"hello","title":"example glossary"}}',
            'recurse'  => true,
        ],
        [
            'objects'  => [
                '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML"],"para":"A meta-markup language, used to create markup languages such as DocBook.","leftOnly":["things"]},"GlossSee":"markup","leftOnly":{"leftKey":"leftValue"},"GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","bothSides":{"leftKey":"leftValue"}}},"title":"S","leftOnly":"hello"},"title":"example glossary"}}',
                '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML"],"para":"A meta-markup language, used to create markup languages such as DocBook."},"GlossSee":"markup","GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","rightOnly":{"rightKey":"rightKey"},"bothSides":{"rightKey":"rightValue"}}},"title":"S"},"title":"example glossary","rightOnly":"hello"}}',
            ],
            'expected' => '{"glossary":{"GlossDiv":{"GlossList":{"GlossEntry":{"Abbrev":"ISO 8879:1986","Acronym":"SGML","GlossDef":{"GlossSeeAlso":["GML","XML"],"leftOnly":["things"],"para":"A meta-markup language, used to create markup languages such as DocBook."},"GlossSee":"markup","GlossTerm":"Standard Generalized Markup Language","ID":"SGML","SortAs":"SGML","bothSides":{"leftKey":"leftValue","rightKey":"rightValue"},"leftOnly":{"leftKey":"leftValue"},"rightOnly":{"rightKey":"rightKey"}}},"leftOnly":"hello","title":"S"},"rightOnly":"hello","title":"example glossary"}}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_UNIQUE_ARRAYS,
        ],
        [
            'objects'  => [
                '{"arr":[{"key1":"value1"}]}',
                '{"arr":[{"key2":"value2"}]}',
                '{"arr":[{"key3":"value3"}]}',
            ],
            'expected' => '{"arr":[{"key1":"value1","key2":"value2","key3":"value3"}]}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES | OBJECT_MERGE_OPT_UNIQUE_ARRAYS
        ],
        [
            'objects'  => [
                '{"arr":[{"key1":"value1","arr":[{"key11":"value11"}]}]}',
                '{"arr":[{"key2":"value2","arr":[{"key22":"value22"}]}]}',
                '{"arr":[{"key3":"value3","arr":[{"key33":"value33"}]}]}',
            ],
            'expected' => '{"arr":[{"key1":"value1","key2":"value2","key3":"value3","arr":[{"key11":"value11","key22":"value22","key33":"value33"}]}]}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES | OBJECT_MERGE_OPT_UNIQUE_ARRAYS
        ],
        [
            'objects'  => [
                '{"arr":[{"key1":"value1","arr":[{"key11":"value11"}]}]}',
                '{"arr":["not an array"]}',
                '{"arr":[7]}',
            ],
            'expected' => '{"arr":[7]}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES | OBJECT_MERGE_OPT_UNIQUE_ARRAYS
        ],
        [
            'objects'  => [
                '{"identity_queryCriteria":{"allOf":[{"$ref":"#/definitions/common_queryCriteria"},{"properties":{"filters":{"description":"Filters used to select specific resource scope","type":"array","items":{"type":"object","additionalProperties":false,"properties":{"type":{"description":"Group type","type":"string","enum":["SYSTEM","CONTROLBLADE","DATABLADE","DOMAIN","ZONE","THIRD_PARTY_ZONE","APGROUP","WLANGROUP","INDOORMAP","AP","WLAN","SWITCH_GROUP"]},"value":{"description":"Group ID","type":"string"},"operator":{"description":"operator","type":"string","enum":["eq"]}},"required":["type","value"]}},"options":{"description":"Specified feature required information","type":"object","additionalProperties":false,"properties":{"includeSharedResources":{"description":"Whether to include the resources of parent domain or not","type":"boolean"},"INCLUDE_RBAC_METADATA":{"description":"Whether to include RBAC metadata or not","type":"boolean"},"TENANT_ID":{"description":"Specify Tenant ID for query","type":"string"},"globalFilterId":{"description":"Specify GlobalFilter ID for query","type":"string"},"localUser_auditTime":{"description":"Audit time of local users","type":"object","properties":{"start":{"description":"start time for auditTime","type":"number"},"end":{"description":"end time for auditTime","type":"number"},"interval":{"description":"time interval in second","type":"number"}}},"localUser_firstName":{"description":"First name of local users","type":"string"},"localUser_lastName":{"description":"Last name of local users","type":"string"},"localUser_mailAddress":{"description":"Mail address of local users","type":"string"},"localUser_primaryPhoneNumber":{"description":"Primary phone number of local users","type":"string"},"localUser_displayName":{"description":"Display name of local users","type":"string"},"localUser_userName":{"description":"User name of local users","type":"string"},"localUser_userSource":{"description":"User source of local users","type":"string"},"localUser_subscriberType":{"description":"Subscriber type of local users","type":"string"},"localUser_status":{"description":"Status of local users","type":"string"},"guestPass_displayName":{"description":"Display name of guest pass","type":"string"},"guestPass_expiration":{"description":"Expiration time of guest pass","type":"object","properties":{"start":{"description":"start time of expiration","type":"number"},"end":{"description":"end time of expiration","type":"number"},"interval":{"description":"time interval in second","type":"number"}}},"guestPass_wlan":{"description":"WLAN which used by quest pass","type":"string"}}},"fullTextSearch":{}}}]}}',
                '{"identity_queryCriteria":{"allOf":[{},{"properties":{"fullTextSearch":{"$ref":"#/definitions/common_fullTextSearch"}}}]}}',
            ],
            'expected' => '{"identity_queryCriteria":{"allOf":[{"$ref":"#/definitions/common_queryCriteria"},{"properties":{"filters":{"description":"Filters used to select specific resource scope","type":"array","items":{"type":"object","additionalProperties":false,"properties":{"type":{"description":"Group type","type":"string","enum":["SYSTEM","CONTROLBLADE","DATABLADE","DOMAIN","ZONE","THIRD_PARTY_ZONE","APGROUP","WLANGROUP","INDOORMAP","AP","WLAN","SWITCH_GROUP"]},"value":{"description":"Group ID","type":"string"},"operator":{"description":"operator","type":"string","enum":["eq"]}},"required":["type","value"]}},"options":{"description":"Specified feature required information","type":"object","additionalProperties":false,"properties":{"includeSharedResources":{"description":"Whether to include the resources of parent domain or not","type":"boolean"},"INCLUDE_RBAC_METADATA":{"description":"Whether to include RBAC metadata or not","type":"boolean"},"TENANT_ID":{"description":"Specify Tenant ID for query","type":"string"},"globalFilterId":{"description":"Specify GlobalFilter ID for query","type":"string"},"localUser_auditTime":{"description":"Audit time of local users","type":"object","properties":{"start":{"description":"start time for auditTime","type":"number"},"end":{"description":"end time for auditTime","type":"number"},"interval":{"description":"time interval in second","type":"number"}}},"localUser_firstName":{"description":"First name of local users","type":"string"},"localUser_lastName":{"description":"Last name of local users","type":"string"},"localUser_mailAddress":{"description":"Mail address of local users","type":"string"},"localUser_primaryPhoneNumber":{"description":"Primary phone number of local users","type":"string"},"localUser_displayName":{"description":"Display name of local users","type":"string"},"localUser_userName":{"description":"User name of local users","type":"string"},"localUser_userSource":{"description":"User source of local users","type":"string"},"localUser_subscriberType":{"description":"Subscriber type of local users","type":"string"},"localUser_status":{"description":"Status of local users","type":"string"},"guestPass_displayName":{"description":"Display name of guest pass","type":"string"},"guestPass_expiration":{"description":"Expiration time of guest pass","type":"object","properties":{"start":{"description":"start time of expiration","type":"number"},"end":{"description":"end time of expiration","type":"number"},"interval":{"description":"time interval in second","type":"number"}}},"guestPass_wlan":{"description":"WLAN which used by quest pass","type":"string"}}},"fullTextSearch":{"$ref":"#/definitions/common_fullTextSearch"}}}]}}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES | OBJECT_MERGE_OPT_UNIQUE_ARRAYS
        ],
        [
            'objects'  => [
                '{"key":"value"}',
                '{"key":null}',
                '{"key":"different value"}'
            ],
            'expected' => '{"key":"different value"}',
            'recurse'  => false,
            'opts'     => OBJECT_MERGE_OPT_NULL_AS_UNDEFINED
        ],
        [
            'objects'  => [
                '{"title":"Unique Clients Trend Over Time","queryName":"trendTable","systemOwnerOnly":false,"component":"ReportTable","defaultParameters":{"granularity":"fifteen_minute","metric":"traffic","limit":10},"layout":{"width":"full","widgetTheme":"blue","headers":[{"component":"GranularityFilter","name":"limit","options":{"traffic":"User Traffic","rxBytes":"Rx User","txBytes":"Tx User","0":"All","10":"Top 10 Clients","20":"Top 20 Clients","50":"Top 50 Clients","100":"Top 100 Clients"}},{"component":"PeriodFilter","query":"topClientTrafficPercentage","content":{"text":"These ${name} consume <b>${percentage}</b> ( <b>${traffic}</b> ) of all user traffic ( <b>${totalTraffic}</b> ).","formats":{"totalTraffic":"bytesFormat","percentage":"percentFormat","traffic":"bytesFormat"}}}],"format":"bytesFormat","colors":["#5BA1E0","#76CEF5","#D9E6F5"],"drillDownRoute":"/report/client/${x}","subSections":[{"component":"LineChart","layout":{"width":"full","label":"authMethod","value":"clientCount","title":{"default":"Clients"},"formatMetadata":{"totalClients":"countFormat","subTitle":"${templateData.totalClients}"},"labelFormat":"label-value2","series":[{"color":"#66b1e8","key":"All Radios","values":"unique_users"},{"color":"#46a3bb","key":"2.4 GHz","values":"unique_users_2-4","area":true},{"color":"#beeee0","key":"5 GHz","values":"unique_users_5","area":true},{"color":"#F17CB0"},{"color":"#8c7024"},{"color":"#B276B2"},{"color":"#eddc44"},{"color":"#F15854"},{"color":"#26657c"},{"color":"#4D4D4D"}],"xAxisType":"time","yAxisType":"countFormat"},"title":"Client Count"},{"component":"LineChart","layout":{"hideLegendLabels":true,"xAxisType":"time","yAxisType":"bytesFormat","width":"full","series":[{"color":"#66b1e8","disabled":false,"key":"User Traffic","values":"traffic"},{"color":"#4e9ee6","disabled":false,"key":"Rx User","values":"rxBytes","area":true},{"color":"#cee9fa","disabled":true,"key":"Tx User","values":"txBytes","area":true},{"color":"#F17CB0","disabled":true},{"color":"#8c7024","disabled":true},{"color":"#B276B2","disabled":true},{"color":"#eddc44","disabled":true},{"color":"#F15854","disabled":true},{"color":"#26657c","disabled":true},{"color":"#4D4D4D","disabled":true}]},"title":"Traffic"}],"columns":[{"columnName":"timestamp","displayName":"Time Period","customComponent":"Interval"},{"columnName":"unique_users_2-4","displayName":"2.4 GHz","customComponent":"PercentBar","drillDownRoute":"/report/client/${clientMac}","color":"#5BE0C7"},{"columnName":"unique_users_5","displayName":"5 GHz","customComponent":"PercentBar","drillDownRoute":"/report/client/${clientMac}","hidden":true,"color":"#5BE0C7"},{"columnName":"unique_users","displayName":"Total","customComponent":"PercentBar","color":"#5BE0C7"},{"columnName":"rxBytes","displayName":"Rx User","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"txBytes","displayName":"Tx User","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"traffic","displayName":"User Traffic","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"manufacturer","displayName":"Manufacturer","hidden":true},{"columnName":"osType","displayName":"OS","hidden":true}]},"url":null,"id":16}',
                '{"title":"Top Clients by Traffic Percentile","queryName":"topPercentile","systemOwnerOnly":false,"component":null,"defaultParameters":{"granularity":"all"},"layout":null,"url":null,"id":17}'
            ],
            'expected' => '{"title":"Top Clients by Traffic Percentile","queryName":"topPercentile","systemOwnerOnly":false,"component":"ReportTable","defaultParameters":{"granularity":"all","metric":"traffic","limit":10},"layout":{"width":"full","widgetTheme":"blue","headers":[{"component":"GranularityFilter","name":"limit","options":{"traffic":"User Traffic","rxBytes":"Rx User","txBytes":"Tx User","0":"All","10":"Top 10 Clients","20":"Top 20 Clients","50":"Top 50 Clients","100":"Top 100 Clients"}},{"component":"PeriodFilter","query":"topClientTrafficPercentage","content":{"text":"These ${name} consume <b>${percentage}</b> ( <b>${traffic}</b> ) of all user traffic ( <b>${totalTraffic}</b> ).","formats":{"totalTraffic":"bytesFormat","percentage":"percentFormat","traffic":"bytesFormat"}}}],"format":"bytesFormat","colors":["#5BA1E0","#76CEF5","#D9E6F5"],"drillDownRoute":"/report/client/${x}","subSections":[{"component":"LineChart","layout":{"width":"full","label":"authMethod","value":"clientCount","title":{"default":"Clients"},"formatMetadata":{"totalClients":"countFormat","subTitle":"${templateData.totalClients}"},"labelFormat":"label-value2","series":[{"color":"#66b1e8","key":"All Radios","values":"unique_users"},{"color":"#46a3bb","key":"2.4 GHz","values":"unique_users_2-4","area":true},{"color":"#beeee0","key":"5 GHz","values":"unique_users_5","area":true},{"color":"#F17CB0"},{"color":"#8c7024"},{"color":"#B276B2"},{"color":"#eddc44"},{"color":"#F15854"},{"color":"#26657c"},{"color":"#4D4D4D"}],"xAxisType":"time","yAxisType":"countFormat"},"title":"Client Count"},{"component":"LineChart","layout":{"hideLegendLabels":true,"xAxisType":"time","yAxisType":"bytesFormat","width":"full","series":[{"color":"#66b1e8","disabled":false,"key":"User Traffic","values":"traffic"},{"color":"#4e9ee6","disabled":false,"key":"Rx User","values":"rxBytes","area":true},{"color":"#cee9fa","disabled":true,"key":"Tx User","values":"txBytes","area":true},{"color":"#F17CB0","disabled":true},{"color":"#8c7024","disabled":true},{"color":"#B276B2","disabled":true},{"color":"#eddc44","disabled":true},{"color":"#F15854","disabled":true},{"color":"#26657c","disabled":true},{"color":"#4D4D4D","disabled":true}]},"title":"Traffic"}],"columns":[{"columnName":"timestamp","displayName":"Time Period","customComponent":"Interval"},{"columnName":"unique_users_2-4","displayName":"2.4 GHz","customComponent":"PercentBar","drillDownRoute":"/report/client/${clientMac}","color":"#5BE0C7"},{"columnName":"unique_users_5","displayName":"5 GHz","customComponent":"PercentBar","drillDownRoute":"/report/client/${clientMac}","hidden":true,"color":"#5BE0C7"},{"columnName":"unique_users","displayName":"Total","customComponent":"PercentBar","color":"#5BE0C7"},{"columnName":"rxBytes","displayName":"Rx User","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"txBytes","displayName":"Tx User","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"traffic","displayName":"User Traffic","customComponent":"PercentBar","format":"bytesFormat"},{"columnName":"manufacturer","displayName":"Manufacturer","hidden":true},{"columnName":"osType","displayName":"OS","hidden":true}]},"url":null,"id":17}',
            'recurse'  => true,
            'opts'     => OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES | OBJECT_MERGE_OPT_UNIQUE_ARRAYS | OBJECT_MERGE_OPT_NULL_AS_UNDEFINED
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
                sprintf(
                    'json_decode returned error while processing test "%s": %s; json=%s',
                    $test,
                    json_last_error_msg(),
                    $json
                )
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
                    $actual = object_merge_recursive_opts($test['opts'], ...$objects);
                } else {
                    $actual = object_merge_recursive(...$objects);
                }
            } elseif (isset($test['opts'])) {
                $actual = object_merge_opts($test['opts'], ...$objects);
            } else {
                $actual = object_merge(...$objects);
            }
            $this->assertEquals($expected, $actual);
//            var_dump($actual);
        }
    }
}