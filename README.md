xml builder utility
==========

[![Build Status](https://travis-ci.org/iwyg/xmlbuilder.png?branch=master)](https://travis-ci.org/iwyg/xmlbuilder)



## Usage

### Create xml from array

```php
<?php

use Thapp\XmlBuilder\XmlBuilder;
use Thapp\XmlBuilder\Normalizer;

$data = array(
  'foo' => 'bar',
  'node' => array(
    '@attributes' => array(
      'date' => '2013-06-06'
    ), 'some string'
  );
);

$xmlBuilder = new XmlBuilder('data');
$xmlBuilder->load($data);

// createXML accepts a boolean value weather to return a string or a DOMDocument
// Set it to `false` if you want to retreive a DOMDocument instead.

echo $xmlBuilder->createXML(true); 

```
prints:

```xml

<data>
  <foo>bar</foo>
  <node date="2013-06-06">some string</node>
</data>

```

XmlBuilder by defaul will create attributes on a dom node by itself as long as your key name statrts with '@', 
however `@attributes` expects an array of key value pairs wereas a key like `@key` would accept only scalar values (string, int, float, or boolean). 

### Map keys to become attributes

```php

$data = array('foo' => 'bar', 'bar' => 'baz');

$xmlBuilder = new XmlBuilder('response');
$XmlBuilder->load($data);


$XmlBuilder->setAttributeMapp(array('response' => array('foo')));
echo $XmlBuilder->createXML();
```

Prints: 

```xml
<response foo="bar">
  <bar>baz</bar>
</response>
```


### Create xml from an Object

```php
<?php

class DataObject
{
  protected $foo = 'bar';
  
  public $bar = 'baz';
  
  public function getFoo()
  {
    return $this->foo;
  }
}
```
XmlBuilder's Normalizer Object is aware of the getter methods of an object

```php


$object = new DataObject('data');

$xmlBuilder->load($object);


echo $xmlBuilder->createXML(true);


```

prints:

```xml
 
 <data>
  <foo>bar</foo>
  <bar>baz</bar>
 </data>
```

### Singularize child names

```php

$xmlBuilder->setSingularizer(function ($name) {

  if ('entries' === $name) {
    return 'entry';
  }
  
  return $name;
});

$entries = array(
  'entries' => array(
    'foo',
    'bar',
    'baz',
  )
);

$xmlBuilder->load($entries);

echo $xmlBuilder->createXML();

````

prints: 

```xml
<data>
  <entries>
    <entry>foo</entry>
    <entry>bar</entry>
    <entry>baz</entry>
  </entries>
</data>
```



