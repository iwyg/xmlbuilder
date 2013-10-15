xml builder utility
==========

Build xml structures from arrays or objects, convert xml structures to array
data structures. 

[![Build Status](https://travis-ci.org/iwyg/xmlbuilder.png?branch=master)](https://travis-ci.org/iwyg/xmlbuilder)



## Installation

Using composer

Add `thapp\xmlbuilder` to your composer.json file.  

```js
"require": {
	"php":">=5.3.7"
	"thapp/xmlbuilder": "v0.1.*"
}
```

Run `composer update` or `composer install` (if this is a clean composer project)

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

$data = array('id' => 12, 'bar' => 'baz');

$xmlBuilder = new XmlBuilder('response');
$XmlBuilder->load($data);


$XmlBuilder->setAttributeMapp(array('response' => array('id')));
echo $XmlBuilder->createXML();
```

Prints: 

```xml
<response id="12">
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
<?php

//...

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


## Loading xml strings and files

XmlBuilder let you load xml strings or files quite easily. The `loadXML` method
accepts 3 arguments: 

 - (string)xml: the xml source. can be a xml string or filename
 - (bool)sourceIsString: the xml source is a xml string or a file 
 - (bool)simpleXml: return an instance of
   `\Thapp\XmlBuilder\Dom\SimpleXMLElement`instead of
   `\Thapp\XmlBuilder\Dom\DOMDocument` 

```php
<?php
// ...
$xml = $xmlBuilder->loadXML('myxmlfile.xml', false);
// or
$xml = $xmlBuilder->loadXML('<data><foo></foo></data>', true);
```

## To array conversion

```php
<?php
// ...

$xml   = $xmlBuilder->loadXML('<data><foo>bar</foo></data>', true);
$array = $xmlBuilder->toArray($xml); // array('data' => array('foo' => 'bar')); 

```

results:

```php
<?php

//...

array(
'data' => array(
	'foo' => 'bar'
	)
); 

```

The array conversion is alos aware of singulars and plurals. Just like the
`setSingularizer` method you can call `setPluralizer`

```php
<?php

//...

$xmlBuilder->setPluralizer(function ($name) {
	if ('entry' === $name) {
		return 'entries';
	}
}); 

```
Given a xml structure like

```xml
<data>
	<entries>
		<entry>foo</entry>
		<entry>bar</entry>
	</entries>
</data>
```

the resulting array, without pluralizer set would look like this

```php
<?php
// ...
array(
	'data' => array('entries' => array(
		'entry' => array('foo', 'bar')
	))
);
```

with pluralizer

```php
<?php
// ...
array(
	'data' => array(
		'entries' => array('foo', 'bar')
	)
);
```

Documentation will be updated shortly. 
