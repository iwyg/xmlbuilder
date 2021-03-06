<?php

/**
 * This File is part of the Thapp\XsltBridge package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder\Tests;

use Mockery as m;
use \ReflectionClass;
use Thapp\XmlBuilder\Normalizer;

class NormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassName
     */
    protected $normalizer;

    /**
     * setUp
     *
     * @access protected
     * @return mixed
     */
    protected function setUp()
    {
        $this->normalizer = new Normalizer;
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

    public function stringProvider()
    {
        return array(
            array('foo-bar', 'fooBar'),
            array('foo-bar', 'foo_bar'),
            array('foo-bar', 'foo:bar'),
            array('foo-bar', 'foo.bar'),
            array('foo', '_foo'),
            array('foo', '%foo')
        );
    }

    /**
     * @test
     * @dataProvider stringProvider
     */
    public function testNormalize($expected, $value)
    {
        $this->assertEquals($expected, $this->normalizer->normalize($value));
    }

    /**
     * @test
     */
    public function testConvertObjectToArray()
    {
        $object = new Stubs\ConvertToArrayStub;
        $data   = $this->normalizer->ensureArray($object);

        $this->assertEquals(array('foo' => 'foo', 'bar' => 'bar'), $data);
    }

    /**
     * @test
     */
    public function testConvertObjectToArrayExtened()
    {
        $data = array('foo' => new SinglePropertyStub);
        $normalized = $this->normalizer->ensureArray($data);

        $this->assertEquals(array('foo' => array('baz' => 'bazvalue')), $normalized);
    }

    /**
     * @test
     */
    public function testConvertNestedObjectProperties()
    {
        $data = array('foo' => new NestedPropertyStub);
        $normalized = $this->normalizer->ensureArray($data);

        $this->assertEquals(array('foo' => array('baz' => array('foo' => 'foo', 'bar' => 'bar'))), $normalized);
    }

    /**
     * @test
     */
    public function testConvertIgnoreClasses()
    {
        $this->normalizer->addIgnoredObject(__NAMESPACE__.'\NestedPropertyStub');
        $data = array('foo' => array('bar' => new NestedPropertyStub));
        $normalized = $this->normalizer->ensureArray($data);

        $this->assertEquals(array('foo' => array()), $normalized);
    }

    /**
     * @test
     */
    public function testConvertArrayableObjectToArray()
    {
        $data = array('foo' => 'foo', 'bar' => 'bar');
        $object = new Stubs\ArrayableStub($data);
        $this->assertEquals($data, $this->normalizer->ensureArray($object));
    }

    /**
     * @test
     */
    public function testConvertObjectToArrayIgnoreRecursion()
    {
        $data = array('bar' => 'bar', 'foo' => array());

        $objectA = new Stubs\ConvertToArrayStub();

        $foo = array($objectA);
        $objectA->setFoo($foo);

        $out = $this->normalizer->ensureArray($objectA);
        $this->assertEquals(array(), $out['foo']);
    }

    /**
     * @test
     */
    public function testConvertArrayableObjectToArrayIgnoreAttributes()
    {
        $this->normalizer->setIgnoredAttributes(array('foo'));

        $data = array('foo' => 'foo', 'bar' => 'bar');
        $object = new Stubs\ArrayableStub($data);

        $this->assertEquals(array('bar' => 'bar'), $this->normalizer->ensureArray($data));
        $this->assertEquals(array('bar' => 'bar'), $this->normalizer->ensureArray($object));
    }
}

class SinglePropertyStub
{
    public $baz = 'bazvalue';
}

class NestedPropertyStub
{
    public $baz;
    public function __construct()
    {
        $this->baz = new Stubs\ConvertToArrayStub;
    }
}
