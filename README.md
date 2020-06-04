# php-object-merge

This is a simple library that facilitates the merging of two or more PHP `stdClass` object properties

[![Build Status](https://travis-ci.com/dcarbone/php-object-merge.svg?branch=master)](https://travis-ci.com/dcarbone/php-object-merge)

## Non-Recursive
The fastest approach to merging two objects will simply apply the fields present in the list of `...$others`
to the root object.
```php
$o1 = json_decode('{"key": "value"}');
$o2 = json_decode('{"key2": "value2"}');
$o3 = json_decode('{"key3": "value3"}');

$out = object_merge($o1, $o2, $o3);

var_dump($out);

/*
class stdClass#55 (3) {
  public $key =>
  string(5) "value"
  public $key2 =>
  string(6) "value2"
  public $key3 =>
  string(6) "value3"
}
*/
```

## Recursive
If you require recursive merging of child objects, that is also possible:

```php
$o1 = json_decode('{"key": ["one"]}');
$o2 = json_decode('{"key": ["two"]}');
$o3 = json_decode('{"key": ["three"]}');

$out = object_merge_recursive($o1, $o2, $o3);

var_dump($out);

/*
class stdClass#56 (1) {
  public $key =>
  array(3) {
    [0] =>
    string(3) "one"
    [1] =>
    string(3) "two"
    [2] =>
    string(5) "three"
  }
}
*/
```

## Merge Options
The `object_merge` and `object_merge_recursive` functions have sister functions named `object_merge_opts` and
`object_merge_recursive_opts` respectively.  Each of these has a required `$opts` argument that must be a bitwise
inclusive or of your desired options.

#### `OBJECT_MERGE_OPT_CONFLICT_OVERWRITE`

This is the default option.

This means that when the provided root object already has a field seen in one of the `...$others`, the value of the LAST
of the `$others` objects will ultimately be used

Example:
```php
$o1 = json_decode('{"key":'.PHP_INT_MAX.'}');
$o2 = json_decode('{"key":true');
$o3 = json_decode('{"key":"not a number"}');

$out = object_merge($o1, $o2, $o3);

var_dump($out);

/*
class stdClass#56 (1) {
  public $key =>
  string(12) "not a number"
}
*/
```

#### `OBJECT_MERGE_OPT_CONFLICT_EXCEPTION`

When this is provided, an exception will be raised if there is a type mismatch

Example:
```php
$o1 = json_decode('{"key":'.PHP_INT_MAX.'}');
$o2 = json_decode('{"key":true');
$o3 = json_decode('{"key":"not a number"}');

$out = object_merge_opts(OBJECT_MERGE_OPT_CONFLICT_EXCEPTION, $o1, $o2, $o3);

// UnexpectedValueException thrown
```

#### `OBJECT_MERGE_OPT_UNIQUE_ARRAYS`

*NOTE*: This only has an effect when doing a *recursive* merge!

When this is provided, any seen array value that does not have a type conflict with an existing field type has its value
pass through `array_values(array_unique($v))`.

This has the net effect of returning a re-indexed array consisting of only unique values.

Example:
```php
$o1 = json_decode('{"key":["one"]}');
$o2 = json_decode('{"key":["one","two"]}');
$o3 = json_decode('{"key":["one","two","three"]}');

$out = object_merge_recursive_opts(OBJECT_MERGE_OPT_UNIQUE_ARRAYS, $o1, $o2, $o3);

var_dump($out);

/*
class stdClass#57 (1) {
  public $key =>
  array(3) {
    [0] =>
    string(3) "one"
    [1] =>
    string(3) "two"
    [2] =>
    string(5) "three"
  }
}
*/
```