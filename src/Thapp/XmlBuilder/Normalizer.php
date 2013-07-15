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
     * @return array
     */
    public function ensureArray($data)
    {
        $out = null;

        switch (true) {
            case $this->isTraversable($data):
                $out = $this->recursiveConvertArray($data);
                break;
            case is_object($data):
                $out = $this->convertObject($data);
                break;
            default:
                break;
        }
        return $out;
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

                $attrValue = $this->ensureArray($value);
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
                $data = $this->ensureArray($data, $ignoreobjects);
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
    protected function isArrayable($reflection)
    {
        return $reflection->hasMethod('toArray') and $reflection->getMethod('toArray')->isPublic();
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
        if ($this->isTraversable($data)) {
            return $this->ensureArray($data);
        }

        $reflection  = new ReflectionObject($data);

        if ($this->isArrayAble($reflection)) {
            $data = $data->toArray();
            return $this->ensureArray($data);
        }


        $methods       = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $properties    = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $out = array();
        $hash = spl_object_hash($data);
        $circularReference = in_array($hash, $this->objectcache);
        $this->objectcache[] = $hash;

        if (!$circularReference) {
            foreach ($methods as $method) {

                if ($this->isGetMethod($method)) {

                    $attributeName  = substr($method->name, 3);
                    $attributeValue = $method->invoke($data);

                    $nkey = $this->normalize($attributeName);
                    if (is_callable($attributeValue) || in_array($nkey, $this->ignoredAttributes)) {
                        continue;
                    }

                    if (null !== $attributeValue && !is_scalar($attributeValue)) {
                        if (is_object($attributeValue)) {
                            $attributeValue = $this->convertObject($attributeValue);
                        } else {
                            $attributeValue = $this->recursiveConvertArray($attributeValue);
                        }
                    }

                    $out[$nkey] = $attributeValue;
                }
            }

            foreach ($properties as $property) {
                $prop =  $property->getName();
                $name =  $this->normalize($prop);

                if (in_array($name, $this->ignoredAttributes)) {
                    continue;
                }

                try {
                    $value = $data->{$prop};
                    $out[$prop] = $value;
                } catch (\Exception $e) {}
            }
        } else {
            return;
        }

        return $out;
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
            $value = $this->isAllUpperCase($value) ?
                strtolower(trim($value, '_-#$%')) :
                snake_case(trim($value, '_-#$%'));
            $this->normalized[$ovalue] = strtolower(preg_replace('/[^a-zA-Z0-9(^@)]+/', '-', $value));
        }

        return $this->normalized[$ovalue];
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
     * isTraversable
     *
     * @param mixed $data
     * @access protected
     * @return boolean
     */
    protected function isTraversable($data)
    {
        return is_array($data) || $data instanceof \Traversable;
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
}
