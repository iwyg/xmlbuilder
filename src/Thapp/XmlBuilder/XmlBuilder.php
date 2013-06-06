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

use \Closure;
use \DOMNode;
use \DOMDocument;

/**
 * Class: XMLBuilder
 *
 *
 * @package Thapp\XmlBuilder
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class XMLBuilder
{
    /**
     * singulars
     *
     * @var bool
     */
    protected $singulars = false;

    /**
     * singularizer
     *
     * @var Closure
     */
    protected $singularizer;

    /**
     * normalizer
     *
     * @var Thapp\XsltBridge\Normalizer
     */
    protected $normalizer;

    /**
     * data
     *
     * @var array
     */
    protected $data;

    /**
     * attributemap
     *
     * @var array
     */
    protected $attributemap = array();

    /**
     * dom
     *
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * encoding
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Create new XmlBuilder.
     * @param string $name root element name
     * @param NormalizerInterface $normalizer
     *
     * @access public
     */
    public function __construct($name = null, NormalizerInterface $normalizer = null)
    {
        $this->setRootname($name);
        $this->setNormalizer($normalizer);
    }

    /**
     * setName
     *
     * @param mixed $name
     * @access public
     * @return void
     */
    public function setName($name = null)
    {
        $this->rootNa = is_null($name) ? 'data' : $name;
    }

    /**
     * setNormalizer
     *
     * @param NormalizerInterface $normalizer
     * @access public
     * @return void
     */
    public function setNormalizer(NormalizerInterface $normalizer = null)
    {
        if (is_null($normalizer)) {
            $normalizer = new Normalizer;
        }

        $this->normalizer = $normalizer;
    }

    /**
     * setEncoding
     *
     * @param mixed $encoding
     * @access public
     * @return void
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * setRootname
     *
     * @param mixed $name
     * @access public
     * @return void
     */
    public function setRootname($name = null)
    {
        $this->rootName = is_null($name) ? 'data' : $name;
    }

    /**
     * setAttributeMapp
     *
     * @param array $map
     * @access public
     * @return void
     */
    public function setAttributeMapp(array $map)
    {
        $this->attributemap = $map;
    }

    /**
     * load
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function load($data)
    {
        $this->data = $data;
    }

    /**
     * getNormalizer
     *
     * @access public
     * @return Thapp\XmlBuilder\NormalizerInterface
     */
    public function getNormalizer()
    {
        return !is_null($this->normalizer) ? $this->normalizer : new Normalizer;
    }

    /**
     * createXML
     *
     * @access public
     * @return \DOMDocument|string
     */
    public function createXML($asstring = false)
    {
        $this->dom = new DOMDocument('1.0', $this->encoding);

        $xmlRoot = $this->rootName;
        $root = $this->dom->createElement($xmlRoot);

        $this->buildXML($root, $this->data);
        $this->dom->appendChild($root);

        return $asstring ? $this->dom->saveXML() : $this->dom;
    }

    /**
     * setSingularizer
     *
     * @param mixed $
     * @param mixed $singularizer
     * @access public
     * @return void
     */
    public function setSingularizer(Closure $singularizer)
    {
        $this->singulars = true;
        $this->singularizer = $singularizer;
    }

    /**
     * normalize
     *
     * @param mixed $name
     * @access protected
     * @return string
     */
    protected function normalize($name)
    {
        return $this->getNormalizer()->normalize($name);
    }

    /**
     * buildXML
     *
     * @param DOMNode $DOMNode
     * @param mixed $data
     * @access protected
     * @return void
     */
    protected function buildXML(DOMNode &$DOMNode, $data, $ignoreObjects = false)
    {
        $data = $this->getNormalizer()->ensureArray($data);

        if (is_null($data)) {
            return;
        }

        $isIndexedArray = ctype_digit(implode('', array_keys($data)));
        $hasAttributes = false;

        foreach ($data as $key => $value) {

            if (!is_scalar($value)) {

                if (!$value = $this->getNormalizer()->ensureArray($value, $ignoreObjects)) {
                    continue;
                }
            }

            if ($this->mapAttributes($DOMNode, $this->normalize($key), $value)) {
                $hasAttributes = true;
                continue;
            }

            if (is_array($value) && !is_int($key)) {
                $keys = array_keys($value);
                // is numeric array
                if (ctype_digit(implode('', $keys))) {

                    foreach ($value as $arrayValue) {
                        $this->appendDOMNode($DOMNode, $this->singularize($this->normalize($key)), $arrayValue);
                    }
                    continue;
                }
            } elseif (is_int($key) || !$this->isValidNodeName($key)) {
                $key = 'item';
            }

            if ($this->isValidNodeName($key)) {
                $this->appendDOMNode($DOMNode, $this->normalize($key), $value, $hasAttributes);
            }
        }
    }

    /**
     * singularize
     *
     * @param mixed $value
     * @access protected
     * @return string
     */
    protected function singularize($value)
    {
        if (!$this->singulars) {
            return $value;
        }
        $fn = $this->singularizer;
        return $fn($value);
    }

    /**
     * mapAttributes
     *
     * @access protected
     * @return boolean
     */
    protected function mapAttributes(DOMNode &$DOMNode, $key, $value)
    {
        if ($attrName = $this->isAttribute($DOMNode, $key)) {

            if (is_array($value)) {
                foreach ($value as $attrKey => $attrValue) {
                    $DOMNode->setAttribute($attrKey, $attrValue);
                }
            } else {
                $DOMNode->setAttribute($attrName, (string)$value);
            }
            return true;
        }
        return false;
    }

    /**
     * isAttribute
     *
     * @param DOMNode $parent
     * @param mixed $key
     * @access protected
     * @return string|boolean
     */
    protected function isAttribute(DOMNode $parent, $key)
    {
        if (strpos($key, '@') === 0 && $this->isValidNodeName($attrName = substr($key, 1))) {
            return $attrName;
        }

        if ($this->isMappedAttribute($parent->nodeName, $key) && $this->isValidNodeName($key)) {
            return $key;
        }
        return false;
    }

    /**
     * isMappedAttribute
     *
     * @param mixed $name
     * @param mixed $key
     * @access public
     * @return boolean
     */
    public function isMappedAttribute($name, $key)
    {
        $map = isset($this->attributemap[$name]) ? $this->attributemap[$name] : array();

        if (isset($this->attributemap['*'])) {
            $map = array_merge($this->attributemap['*'], $map);
        }

        return in_array($key, $map);
    }

    /**
     * setElementValue
     *
     * @param DOMNode $DOMNode
     * @param mixed $value
     */
    protected function setElementValue($DOMNode, $value = null)
    {
        switch (true) {
            case $value instanceof \SimpleXMLElement:
                $node = dom_import_simplexml($value);
                $this->dom->importNode($node);
                $DOMNode->appendChild($node);
                break;
            case $value instanceof \DOMNode:
                $DOMNode->appendChild($value);
                break;
            case is_array($value) || $value instanceof \Traversable:
                $this->buildXML($DOMNode, $value);
                return true;
            case is_numeric($value):
                if (is_string($value)) {
                    return $this->createTextNodeWithTypeAttribute($DOMNode, (string)$value, 'string');
                }
                return $this->createText($DOMNode, (string)$value);
            case is_bool($value):
                return $this->createText($DOMNode, $value ? 'yes' : 'no');
            case is_string($value):
                if (preg_match('/(<|>|&)/i', $value)) {
                    return $this->createCDATASection($DOMNode, $value);
                }
                return $this->createText($DOMNode, $value);
            default:
                return $value;
        }
    }

    /**
     * isValidNodeName
     *
     * @param mixed $name
     * @access protected
     * @return boolean
     */
    protected function isValidNodeName($name)
    {
        return !empty($name) && false === strpos($name, ' ') && preg_match('#^[\pL_][\pL0-9._-]*$#ui', $name);
    }

    /**
     * appendDOMNode
     *
     * @param DOMNode $DOMNode
     * @param string  $name
     * @param mixed   $value
     * @param boolean $hasAttributes
     * @access protected
     * @return void
     */
    protected function appendDOMNode($DOMNode, $name, $value = null, $hasAttributes = false)
    {
        $element = $this->dom->createElement($name);

        if ($hasAttributes && ($name === 'text' || $name === 'value')) {
            $this->setElementValue($DOMNode, $value);
        } else if ($this->setElementValue($element, $value)) {
            $DOMNode->appendChild($element);
        }
    }

    /**
     * createText
     *
     * @param DOMNode $DOMNode
     * @param string  $value
     * @access protected
     * @return boolean
     */
    protected function createText($DOMNode, $value)
    {
        $text = $this->dom->createTextNode($value);
        $DOMNode->appendChild($text);
        return true;
    }

    /**
     * createCDATASection
     *
     * @param DOMNode $DOMNode
     * @param string  $value
     * @access protected
     * @return boolean
     */
    protected function createCDATASection($DOMNode, $value)
    {
        $cdata = $this->dom->createCDATASection($value);
        $DOMNode->appendChild($cdata);
        return true;
    }

    /**
     * createTextNodeWithTypeAttribute
     *
     * @param DOMNode $DOMNode
     * @param mixed   $value
     * @param string  $type
     * @access protected
     * @return boolean
     */
    protected function createTextNodeWithTypeAttribute($DOMNode, $value, $type = 'int')
    {
        $text = $this->dom->createTextNode($value);
        $attr = $this->dom->createAttribute('type');
        $attr->value = $type;
        $DOMNode->appendChild($text);
        $DOMNode->appendChild($attr);
        return true;
    }
}
