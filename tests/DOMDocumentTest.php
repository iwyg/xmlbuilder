<?php

/**
 * This File is part of the tests package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XsltBridge\Tests;

use Thapp\XmlBuilder\Dom\DOMElement;
use Thapp\XmlBuilder\Dom\DOMDocument;

/**
 * @class DOMDocumentTest
 * @package
 * @version $Id$
 */
class DOMDocumentTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateElement()
    {
        $doc = new DOMDocument();
        $this->assertInstanceOf('Thapp\XmlBuilder\Dom\DOMElement', $doc->createElement('foo'));
    }

    public function testAppendForeignElement()
    {
        $doc = new DOMDocument;
        $dom = new \DOMDocument;

        $foo = $doc->createElement('foo');
        $doc->appendChild($foo);
        $bar = $dom->createElement('bar');
        $baz = $dom->createElement('baz');

        $doc->appendDomElement($bar, $foo);

        $expected = new \DOMDocument;
        $expected->loadXML('<foo><bar></bar></foo>');

        $this->assertEqualXMLStructure($expected->firstChild, $doc->firstChild);

        $expected = new \DOMDocument;
        $expected->loadXML('<foo><bar></bar><baz></baz></foo>');

        $foo->appendDomElement($baz);

        $this->assertEqualXMLStructure($expected->firstChild, $foo);



    }

    public function testXpath()
    {
        if (!extension_loaded('xsl')) {
            $this->markTestSkipped();
        }
        $doc = new DOMDocument;
        $doc->loadXML('<data><foo id="1"><baz id="2" value="foo"></baz></foo></data>');

        $result = $doc->xPath('//*/@id');

        $this->assertInstanceOf('DOMNodeList', $result);
        $this->assertSame(2, $result->length);

        $result = $doc->firstChild->xPath('//foo');

        $this->assertInstanceOf('DOMNodeList', $result);
        $this->assertSame(1, $result->length);
    }

}
