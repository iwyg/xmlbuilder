xml builder utility
==========

[![Build Status](https://travis-ci.org/iwyg/xmlbuilder.png?branch=master)](https://travis-ci.org/iwyg/xmlbuilder)

Xml Builder

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

/** outputs 

<data>
  <foo>bar</foo>
  <node date="2013-06-06">some string</node>
</data>

*/


?>
