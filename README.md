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
echo $xmlBuilder->createXML(true); 

```
prints:

```xml

<data>
  <foo>bar</foo>
  <node date="2013-06-06">some string</node>
</data>

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



