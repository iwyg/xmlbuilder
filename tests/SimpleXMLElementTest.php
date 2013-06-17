<?php

/**
 * This File is part of the Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder;

use \DOMDocument;
use Thapp\XmlBuilder\Dom\SimpleXMLElement;

class SimpleXMLElementTest extends \PHPUnit_Framework_TestCase
{

    protected $xmlErrors;

    protected function tearDonwn()
    {
        //parent::tearDown();

        libxml_clear_errors();
        $this->xmlErrors = array();
    }

    /**
     * @test
     * @dataProvider cdataStringProvider
     */
    public function testInsertCDATASectionFromString($xml, $cdata, $expected)
    {
        $simpleXML = $this->getSimpleXmlElement($xml);
        $simpleXML->insert->addCDATASection($cdata);

        $this->assertXmlStringEqualsXmlString($expected, $simpleXML->asXML());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function testInsertCDATASectionShouldThrowException()
    {
        $simpleXML = $this->getSimpleXmlElement('<foo><bar></bar></foo>');
        $simpleXML->insert->addCDATASection(array());
    }

    /**
     * @test
     * @dataProvider cdataDOMProvider
     */
    public function testInsertCDATASectionFromDom($xml, $cdata, $expected)
    {
        $simpleXML = $this->getSimpleXmlElement($xml);
        $simpleXML->insert->addCDATASection($cdata);

        $this->assertXmlStringEqualsXmlString($expected, $simpleXML->asXML());
    }

    /**
     * @test
     */
    public function testAppendNodeFromString()
    {
        $simpleXML = $this->getSimpleXmlElement('<data><foo></foo></data>');
        $simpleXML->foo->appendChildFromXmlString('<bar></bar>');
        $this->assertXmlStringEqualsXmlString('<data><foo><bar></bar></foo></data>', $simpleXML->asXML());

    }

    /**
     * @test
     */
    public function testAppendNodeFromHtml()
    {
        $simpleXML = $this->getSimpleXmlElement('<data><foo></foo></data>');
        $simpleXML->foo->appendChildFromHtmlString('<p>foo</p>');
        $this->assertXmlStringEqualsXmlString('<data><foo><p>foo</p></foo></data>', $simpleXML->asXML());
    }

    /**
     * @test
     */
    public function testAppendChild()
    {
        $simpleXML = $this->getSimpleXmlElement('<data><foo></foo></data>');
        $child     = $this->getSimpleXmlElement('<data><bar></bar></data>');

        $simpleXML->foo->appendChildNode($child->bar);
        $this->assertXmlStringEqualsXmlString('<data><foo><bar></bar></foo></data>', $simpleXML->asXML());
    }


    /**
     * cdataStringProvider
     *
     * @access public
     * @return array
     */
    public function cdataStringProvider()
    {
        return array(
            array(
                '<data><insert></insert></data>',
                'some text',
                '<data><insert><![CDATA[some text]]></insert></data>',
            )
        );
    }

    /**
     * cdataDOMProvider
     *
     * @access public
     * @return array
     */
    public function cdataDOMProvider()
    {
        $dom = new DOMDocument();
        $dom->loadXML('<inserted>some text</inserted>', LIBXML_NOXMLDECL);
        $element = new \DOMElement('inserted', 'some text');
        $simple  = simplexml_load_string('<inserted>some text</inserted>');

        return array(
            array(
                '<data><insert></insert></data>',
                $dom,
                '<data><insert><![CDATA[<inserted>some text</inserted>]]></insert></data>',
            ),

            array(
                '<data><insert></insert></data>',
                $element,
                '<data><insert><![CDATA[<inserted>some text</inserted>]]></insert></data>',
            ),

            array(
                '<data><insert></insert></data>',
                $simple,
                '<data><insert><![CDATA[<inserted>some text</inserted>]]></insert></data>',
            )
        );
    }

    /**
     * getSimpleXmlElement
     *
     * @param string $xml
     * @access protected
     * @return SimpleXMLElement
     */
    protected function getSimpleXmlElement($xml)
    {
        if (!$xml = $this->loadXML($xml)) {
            $errors = array();
            foreach ($this->xmlErrors as $error) {
                $errors[]  = $error->message;
            }
            throw new \RuntimeException(implode('\n', $errors));
        }
        return $xml;
    }
    /**
     * loadXML
     *
     * @param mixed $string
     * @access protected
     * @return mixed
     */
    protected function loadXML($string,  $class = 'Thapp\XmlBuilder\Dom\SimpleXMLElement')
    {

        $errored = false;

        $usedInternalErrors = libxml_use_internal_errors(true);
        $externalEntitiesDisabled = libxml_disable_entity_loader(false);
        libxml_clear_errors();

        $dom = new DOMDocument('1.0', 'UTF-8');

        // LIBXML_NONET prevents local and remote file inclusion attacks
        try {
            $dom->loadXML($string, LIBXML_NONET | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $errors = libxml_get_errors();

        if (!empty($errors)) {
            $this->xmlErrors = $errors;
            $errored = true;
        }

        // restore previous libxml setting:
        libxml_use_internal_errors($usedInternalErrors);
        libxml_disable_entity_loader($externalEntitiesDisabled);

        if ($errored) {
            return false;
        }

        $dom->normalizeDocument();
        $xml = simplexml_import_dom($dom, $class);

        return $xml;
    }
}


