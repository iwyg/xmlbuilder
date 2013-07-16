<?php

/**
 * This File is part of the Thapp\XmlBuilder package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder;

use \ReflectionObject;
use \ReflectionMethod;
use \ReflectionProperty;

/**
 * Class: Normalizer
 *
 *
 * @package Thapp\XmlBuilder
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class Normalizer implements NormalizerInterface
{
    /**
     * objcache
     *
     * @var array
     */
    protected $objectcache = array();

    /**
     * ignoredAttributes
     *
     * @var array
     */
    protected $ignoredAttributes = array();

    /**
     * ignoredObjects
     *
     * @var array
     */
    protected $ignoredObjects = array();

    /**
     * normalized
     *
     * @var array
     */
    protected $normalized = array();

    /**
     * ensureArray
     *
     * @param mixed $data
     * @access public
     * @deprecated
     * @return array
     */
    public function ensureArray($data)
    {
        switch (true) {
        case $this->isTraversable($data):
            return $this->recursiveConvertArray($data);
        case is_object($data):
            return $this->convertObject($data);
        default:
            return;
        }
    }

    /**
     * ensureBildable
     *
     * @param mixed $data
     * @access public
     * @since v0.1.3
     * @return mixed
     */
    public function ensureBuildable($data)
    {
        switch (true) {
        case $this->isXMLElement($data):
            return $data;
        case $this->isTraversable($data):
            return $this->recursiveConvertArray($data);
        case is_object($data):
            return $this->convertObject($data);
        default:
            return;
        }
    }

    /**
     * recursiveConvertArray
     *
     * @param array $data
     * @param mixed $ignoreobjects
     * @access protected
     * @return array
     */
    protected function recursiveConvertArray($data)
    {
        $out = array();

        foreach ($data as $key => $value) {

            $nkey = $this->normalize($key);

            if (in_array($nkey, $this->ignoredAttributes)) {
                continue;
            }

            if (is_scalar($value)) {
                $attrValue = $value;
            } else {
                $attrValue = $this->ensureBuildable($value);
            }

            if (!is_null($attrValue)) {
                $out[$nkey] = $attrValue;
            }

        }
        return $out;
    }

    /**
     * ensureTraversable
     *
     * @param mixed $data
     * @access public
     * @return array
     */
    public function ensureTraversable($data, $ignoreobjects = false)
    {
        if (!$this->isTraversable($data)) {
            if (is_object($data)) {
                $data = $this->ensureBuildable($data, $ignoreobjects);
            }
        }

        return $data;
    }

    /**
     * isArrayAble
     *
     * @param  mixed $reflection a reflection object
     * @access protected
     * @return boolean
     */
    protected function isArrayable($data)
    {
        return $data->hasMethod('toArray') and $data->getMethod('toArray')->isPublic();
    }

    /**
     * isTraversable
     *
     * @param mixed $data
     * @access protected
     * @return boolean
     */
    public function isTraversable($data)
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    /**
     * convertObject
     *
     * @param mixed $data
     * @access protected
     * @return array
     */
    protected function convertObject($data)
    {

        if ($this->isIgnoredObject($data)) {
            return;
        }

        if ($this->isXMLElement($data)) {
            return $data;
        }

        if ($this->isTraversable($data)) {
            return $this->ensureBuildable($data);
        }

        $reflection = new ReflectionObject($data);

        if ($this->isArrayAble($reflection)) {
            $data = $data->toArray();
            return $this->ensureBuildable($data);
        }

        if ($this->isCircularReference($data)) {
            return;
        }


        $out = array();

        $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $this->getObjectGetterValues($methods, $data, $out);

        $this->getObjectProperties($properties, $data, $out);
        return $out;
    }

    /**
     * getObjectGetterValues
     *
     * @param mixed $methods
     * @param array $out
     * @access protected
     * @return mixed
     */
    protected function getObjectGetterValues($methods, $object,  array &$out = array())
    {
        foreach ($methods as $method) {
            $this->getObjectGetterValue($method, $object, $out);
        }
    }
    /**
     * getObjectGetterValue
     *
     * @param ReflectionMethod $method
     * @param array $out
     * @access protected
     * @return array
     */
    protected function getObjectGetterValue(ReflectionMethod $method, $object, array &$out = array())
    {
        if (!$this->isGetMethod($method)) {
            return;
        }

        $attributeName  = substr($method->name, 3);
        $attributeValue = $method->invoke($object);

        $nkey = $this->normalize($attributeName);

        if (is_callable($attributeValue) || in_array($nkey, $this->ignoredAttributes)) {
            continue;
        }

        if (null !== $attributeValue && !is_scalar($attributeValue)) {
            $attributeValue = $this->ensureBuildable($attributeValue);
        }

        $out[$nkey] = $attributeValue;
    }

    /**
     * convertObjectProperties
     *
     * @param array $properties
     * @param array $out
     * @access protected
     * @return mixed
     */
    protected function getObjectProperties(array $properties, $data, array &$out = array())
    {
        foreach ($properties as $property) {
            $prop =  $property->getName();
            if (in_array($name = $this->normalize($prop), $this->ignoredAttributes)) {
               continue;
            }
            $out[$prop] = $this->getObjectPropertyValue($property, $prop, $data);
        }
    }

    /**
     * getPropertyValue
     *
     * @param \ReflectionProperty $property
     * @param mixed $prop
     * @access protected
     * @return mixed
     */
    protected function getObjectPropertyValue(\ReflectionProperty $property, $prop, $data)
    {
        $prop =  $property->getName();

        try {
            $value = $data->{$prop};
        } catch (\Exception $e) {
        }

        if (!is_scalar($value)) {
            $value = $this->ensureBuildable($value);
        }

        return $value;
    }

    /**
     * isCircularReference
     *
     * @access protected
     * @return mixed
     */
    protected function isCircularReference($data)
    {
        $hash = spl_object_hash($data);
        $circularReference = in_array($hash, $this->objectcache);
        $this->objectcache[] = $hash;

        return $circularReference;
    }

    /**
     * normalize
     *
     * @param mixed $value
     * @access public
     * @return string
     */
    public function normalize($value)
    {
        $ovalue = $value;

        if (!isset($this->normalized[$value])) {
            $this->normalized[$ovalue] = $this->normalizeString($value);;
        }

        return $this->normalized[$ovalue];
    }

    /**
     * normalizeString
     *
     * @param mixed $string
     * @access protected
     * @return mixed
     */
    protected function normalizeString($string)
    {
        $value = $this->isAllUpperCase($string) ?
            strtolower(trim($string, '_-#$%')) :
            snake_case(trim($string, '_-#$%'));

        return strtolower(preg_replace('/[^a-zA-Z0-9(^@)]+/', '-', $value));
    }

    /**
     * isAllUpperCase
     *
     * @param mixed $str
     * @access private
     * @return mixed
     */
    private function isAllUpperCase($str)
    {
        $str = preg_replace('/[^a-zA-Z0-9]/', null, $str);
        return ctype_upper($str);
    }

    /**
     * isGetMethod
     *
     * @param mixed $method
     * @access public
     * @return boolean
     */
    public function isGetMethod(\ReflectionMethod $method)
    {
        return 'get' === substr($method->name, 0, 3) && strlen($method->name) > 3 && 0 === $method->getNumberOfRequiredParameters();
    }

    /**
     * setIgnoredAttributes
     *
     * @access public
     * @return mixed
     */
    public function setIgnoredAttributes($attributes)
    {
        if (is_array($attributes)) {
            $this->ignoredAttributes = array_merge($this->ignoredAttributes, $attributes);
            return;
        }
        $this->ignoredAttributes[] = $attributes;
    }

    /**
     * setIgnoredAttributes
     *
     * @param mixed $attributes
     * @access public
     * @return mixed
     */
    public function setIgnoredObjects($classes)
    {
        if (is_array($classes)) {
            foreach ($classes as $classname) {
                $this->addIgnoredObject($classname);
            }
            return;
        }
        return $this->addIgnoredObject($classes);
    }

    /**
     * addIgnoredObject
     *
     * @param mixed $classname
     * @access public
     * @return mixed
     */
    public function addIgnoredObject($classname)
    {
        $this->ignoredObjects[] = strtolower($classname);
    }

    /**
     * isXMLElement
     *
     * @param mixed $data
     * @access public
     * @return boolean
     */
    public function isXMLElement($data)
    {
        return $data instanceof \SimpleXMLElement or $data instanceof \DOMNode;
    }

    /**
     * isIgnoredObject
     *
     * @param mixed $oject
     * @access protected
     * @return mixed
     */
    protected function isIgnoredObject($object)
    {
        return in_array(strtolower($class =
            ($parent = get_parent_class($object)) ? $parent : get_class($object)),
        $this->ignoredObjects);
    }
}
