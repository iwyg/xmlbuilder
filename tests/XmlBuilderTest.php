<?php

/**
 * This File is part of the Thapp\XsltBridge package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XsltBridge\Tests;

use Mockery as m;
use Thapp\XmlBuilder\Normalizer;
use Thapp\XmlBuilder\XmlBuilder;

/**
 * Class: XmlBuilderTest
 *
 * @uses \PHPUnit_Framework_TestCase
 *
 * @package
 * @version
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class XmlBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * setUp
     *
     * @access protected
     * @return mixed
     */
    protected function setUp()
    {
        $normalizer = new Normalizer;
        $this->builder = new XmlBuilder('data', $normalizer);
    }

    /**
     * tearDown
     *
     * @access protected
     * @return mixed
     */
    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function testConstructWithoutNormalizer()
    {
        try {

            $builder = new XmlBuilder;

        } catch (\Exception $e) {
            $this->fail();
        }
    }

    /**
     * @test
     */
    public function testGetNormalizerShouldReturnInstanceOfNormalizerInterface()
    {
        $builder = new XmlBuilder;
        $this->assertInstanceof('Thapp\XmlBuilder\NormalizerInterface', $builder->getNormalizer());
    }

    /**
     * @test
     */
    public function testBuildXML()
    {
        $str  = '<data><foo>bar</foo></data>';
        $data = array('foo' => 'bar');
        $this->builder->load($data);
        $xml  = $this->builder->createXML(true);

        $this->assertXmlStringEqualsXmlString($str, $xml);
    }

    /**
     * @test
     */
    public function testBuildXMLSetAttributes()
    {
        $str  = '<data foo="bar"/>';
        $data = array('foo' => 'bar');
        $this->builder->load($data);
        $this->builder->setAttributeMapp(array('data' => array('foo')));
        $xml  = $this->builder->createXML(true);

        $this->assertXmlStringEqualsXmlString($str, $xml);
    }

    /**
     * @test
     */
    public function testBuildXMLSetNullData()
    {
        $str  = '<data/>';
        $xml  = $this->builder->createXML(true);
        $this->assertXmlStringEqualsXmlString($str, $xml);
    }

    /**
     * @test
     */
    public function testBuildXMLSetAttributesWithPrefix()
    {
        $str  = '<data foo="bar"/>';
        $data = array('@foo' => 'bar');
        $this->builder->load($data);
        $xml  = $this->builder->createXML(true);

        $this->assertXmlStringEqualsXmlString($str, $xml);
    }

    /**
     * @test
     */
    public function testBuildXMLCreateArray()
    {
        $str  = '<data><entries>a</entries><entries>b</entries><entries>c</entries></data>';
        $data = array('entries' => array('a', 'b', 'c'));
        $this->builder->load($data);

        $xml  = $this->builder->createXML(true);
        $this->assertXmlStringEqualsXmlString($str, $xml);
    }

    /**
     * @test
     */
    public function testCreateCdataSection()
    {
        $data = array(
            'text' => '<!-- this should be wrapped in cdata -->'
        );

        $expected = '<data><text><![CDATA[<!-- this should be wrapped in cdata -->]]></text></data>';

        $this->builder->load($data);
        $this->assertXmlStringEqualsXmlString($expected, $this->builder->createXML($data));
    }

    /**
     * @test
     */
    public function testConvertBooleanValues()
    {
        $data = array('foo' => array('@attributes' => array('value' => true)));
        $this->builder->load($data);

        $expected = '<data><foo value="true"></foo></data>';

        $this->assertXmlStringEqualsXmlString($expected, $this->builder->createXML($data));
    }

    /**
     * @test
     */
    public function testBuildXMLCreateArrayAndSingularizeNodeNames()
    {
        $str  = '<data><entry>a</entry><entry>b</entry><entry>c</entry></data>';
        $data = array('entries' => array('a', 'b', 'c'));
        $this->builder->load($data);

        $this->builder->setSingularizer(function ($value) {
            return 'entry';
        });

        $xml  = $this->builder->createXML(true);
        $this->assertXmlStringEqualsXmlString($str, $xml);
    }


    public function testXmlToArray()
    {
        $xml  = '<data><foo></foo></data>';
        $dom  = $this->builder->loadXml($xml, true, false);
        $data = $this->builder->toArray($dom);

        $this->assertTrue(array_key_exists('data', $data));
        $this->assertTrue(array_key_exists('foo', $data['data']));
        $this->assertNull($data['data']['foo']);
    }

    public function testXmlToArrayAttributValues()
    {

        $xml = '<data><foo value="true" int="1" float="0.1"/></data>';
        $dom   = $this->builder->loadXml($xml, true, false);
        $array = $this->builder->toArray($dom);

        $this->assertArrayHasKey('value', $array['data']['foo']['@attributes']);
        $this->assertInternalType('bool', $array['data']['foo']['@attributes']['value']);
        $this->assertArrayHasKey('int', $array['data']['foo']['@attributes']);
        $this->assertInternalType('integer', $array['data']['foo']['@attributes']['int']);
        $this->assertArrayHasKey('float', $array['data']['foo']['@attributes']);
        $this->assertInternalType('float', $array['data']['foo']['@attributes']['float']);
    }

    public function testXmlToArrayArrayStructures()
    {
        $xml = '<foos><foo>1</foo><foo>2</foo><foo>3</foo></foos>';
        $dom = $this->builder->loadXml($xml, true, false);

        $expected = array(
            'foos' => array(
                'foo' => array(1, 2, 3)
            )
        );

        $this->assertSame($expected, $this->builder->toArray($dom));
    }

    public function testXmlToArrayArrayStructuresWithSingularizedNames()
    {
        $this->builder->setPluralizer(function($name) {
            if ('foo' === $name) {
                return 'foos';
            }
            return $name;
        });

        $this->builder->setSingularizer(function($name) {
            if ('foos' === $name) {
                return 'foo';
            }
            return $name;
        });

        $xml = '<foos><foo>1</foo><foo>2</foo><foo>3</foo></foos>';
        $dom = $this->builder->loadXml($xml, true, false);

        $expected = array(
            'foos' => array(1, 2, 3)
        );


        $this->assertSame($expected, $this->builder->toArray($dom));
    }
}
