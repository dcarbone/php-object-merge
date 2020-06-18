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

const OBJECT_MERGE_OPT_CONFLICT_OVERWRITE = 0x0;
const OBJECT_MERGE_OPT_CONFLICT_EXCEPTION = 0x1;
const OBJECT_MERGE_OPT_UNIQUE_ARRAYS = 0x2;
const OBJECT_MERGE_OPT_MERGE_ARRAY_VALUES = 0x4;
const OBJECT_MERGE_OPT_NULL_AS_UNDEFINED = 0x8;

define('OBJECT_MERGE_UNDEFINED', uniqid('__OBJECT_MERGE_UNDEFINED_'));