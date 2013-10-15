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

use Closure;
use DOMNode;
use Thapp\XmlBuilder\XmlLoader;
use Thapp\XmlBuilder\LoaderInterface;
use Thapp\XmlBuilder\Dom\DOMElement;
use Thapp\XmlBuilder\Dom\DOMDocument;
use Thapp\XmlBuilder\Dom\SimpleXMLElement;

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
     * plurals
     *
     * @var bools
     */
    protected $plurals = false;

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
     * loader
     *
     * @var mixed
     */
    protected $loader;

    /**
     * data
     *
     * @var array
     */
    protected $data;

    /**
     * rootName
     *
     * @var string
     */
    protected $rootName;

    /**
     * checkPrefixes
     *
     * @var bool
     */
    protected $checkPrefixes = false;

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
     * indexKey
     *
     * @var string
     */
    protected $indexKey = 'item';

    /**
     * nodeValueKey
     *
     * @var string
     */
    protected $nodeValueKey = 'nodevalue';

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
    public function __construct($name = null, NormalizerInterface $normalizer = null, LoaderInterface $loader = null)
    {
        $this->setRootname($name);
        $this->setNormalizer($normalizer);
        $this->setLoader($loader);
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
     * setLoader
     *
     * @param LoaderInterface $loader
     * @access public
     * @return void
     */
    public function setLoader(LoaderInterface $loader = null)
    {
        if (is_null($loader)) {
            $loader = new XmlLoader;
        }

        $this->loader = $loader;
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
     * setIndexKey
     *
     * @param string $key
     * @access public
     * @return void
     */
    public function setIndexKey($key, $normalize = false)
    {
        if (true !== $normalize and !$this->isValidNodeName($key)) {
           throw new \InvalidArgumentException(sprintf('%s is an invalid node name', $key));
        } else {
           $key = $this->normalizer->normalize($key);
        }

        return $this->indexKey = $key;
    }

    /**
     * setNodeValueKey
     *
     * @param string $key
     *
     * @access public
     * @return void
     */
    public function setNodeValueKey($key, $normalize = false)
    {
        if (true !== $normalize and !$this->isValidNodeName($key)) {
           throw new \InvalidArgumentException(sprintf('%s is an invalid node name', $key));
        } else {
           $key = $this->normalizer->normalize($key);
        }

        return $this->nodeValueKey = $key;
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
     * setPluralizer
     *
     * @param mixed $
     * @param mixed $pluralizer
     * @access public
     * @return void
     */
    public function setPluralizer(Closure $pluralizer)
    {
        $this->plurals = true;
        $this->pluralizer = $pluralizer;
    }

    /**
     * buildXML
     *
     * @param DOMNode $DOMNode
     * @param mixed $data
     * @access protected
     * @return void
     */
    protected function buildXML(DOMNode &$DOMNode, $data)
    {
        $normalizer = $this->getNormalizer();

        if (is_null($data)) {
            return;
        }

        if ($normalizer->isTraversable($data) and !$normalizer->isXMLElement($data)) {
            $this->buildXmlFromTraversable($DOMNode, $normalizer->ensureBuildable($data), $normalizer);
        } else {
            $this->setElementValue($DOMNode, $data);
        }
    }

    /**
     * buildXmlFromTraversable
     *
     * @param DOMNode $DOMNode
     * @param mixed $data
     * @param NormalizerInterface $normalizer
     * @param mixed $ignoreObjects
     * @access protected
     * @return void
     */
    protected function buildXmlFromTraversable(DOMNode $DOMNode, $data, NormalizerInterface $normalizer)
    {
        $isIndexedArray = array_is_numeric($data);
        $hasAttributes = false;

        foreach ($data as $key => $value) {

            if (!is_scalar($value)) {

                if (!$value = $normalizer->ensureBuildable($value)) {
                    continue;
                }
            }

            if ($this->mapAttributes($DOMNode, $normalizer->normalize($key), $value)) {
                $hasAttributes = true;
                continue;
            }

            // set the default index key if there's no other way:
            if (is_int($key) || !$this->isValidNodeName($key)) {
                $key = $this->indexKey;
            }

            if (is_array($value) && !is_int($key)) {

                if (array_is_numeric($value)) {
                    if ($skey = $this->singularize($key) and ($key !== $skey)) {
                        $parentNode = $this->dom->createElement($key);
                        foreach ($value as $arrayValue) {
                            $this->appendDOMNode($parentNode, $skey, $arrayValue);
                        }
                        $DOMNode->appendChild($parentNode);;
                    } else {
                        foreach ($value as $arrayValue) {
                            $this->appendDOMNode($DOMNode, $this->singularize($normalizer->normalize($key)), $arrayValue);
                        }
                    }
                    continue;
                }
            } elseif ($normalizer->isXMLElement($value)) {
                // if this is a non scalar value at this time, just set the
                // value on the element
                $node = $this->dom->createElement($normalizer->normalize($key));
                $DOMNode->appendChild($node);
                $this->setElementValue($node, $value);
                continue;
            }

            if ($this->isValidNodeName($key)) {
                $this->appendDOMNode($DOMNode, $normalizer->normalize($key), $value, $hasAttributes);
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
     * pluralize
     *
     * @param mixed $value
     * @access protected
     * @return mixed
     */
    protected function pluralize($value)
    {
        if (!$this->plurals) {
            return $value;
        }
        $fn = $this->pluralizer;
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
                    $DOMNode->setAttribute($attrKey, $this->getValue($attrValue));
                }
            } else {
                $DOMNode->setAttribute($attrName, $this->getValue($value));
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

            case $this->isSimpleXMLElement($value):
                $node = dom_import_simplexml($value);
                $node = $this->dom->importNode($node, true);
                $DOMNode->appendChild($node);
                break;
            case $value instanceof \DOMDocument:
                $DOMNode->appendDomElement($value->firstChild);
                break;
            case $value instanceof \DOMNode:
                $this->dom->appendDomElement($value, $DOMNode);
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

    protected function getValue($value)
    {
        switch (true) {
        case is_bool($value):
            return $value ? 'true' : 'false';
        case is_numeric($value):
            return ctype_digit($value) ? intval($value) : floatval($value);
        case in_array($value, array('true', 'false', 'yes', 'no')):
            return ('false' === $value || 'no' === $value) ? false : true;
        default:
            return clear_value(trim($value));
        };
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

        if ($hasAttributes && $name === $this->nodeValueKey) {
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

    public function loadXml($xml, $sourceIsString = false, $simpleXml = false)
    {
        $loader = $this->loader->create();
        $loader->setOption('from_string', $sourceIsString);
        $loader->setOption('simplexml', $simpleXml);

        return $loader->load($xml);
    }


    public function toArray(DOMDocument $dom, $checkPrefixes = false)
    {
        $this->checkPrefixes = $checkPrefixes;

        $dom->normalizeDocument();

        $xmlObj = simplexml_import_dom($dom, '\Thapp\XmlBuilder\Dom\SimpleXMLElement');

        $namespaces = $xmlObj->getNamespaces();

        $root = key($namespaces) !== '' ? $this->prefixKey(key($namespaces), $xmlObj->getName()) : $xmlObj->getName();
        $data = $this->parseXML($xmlObj, $namespaces);
        return array($root => $data);
    }
    /**
     * parseXML
     *
     * @param SimpleXMLElement $xml
     * @access protected
     * @return array
     */
    protected function parseXML(SimpleXMLElement $xml, $nestedValues = true)
    {
        $childpool  = $xml->xpath('child::*');
        $attributes = $xml->xpath('./@*');
        $parentName = $xml->getName();


        if (!empty($attributes)) {
            $attrs = array();
            foreach ($attributes as $key => $attribute) {
                $namespaces = $attribute->getnameSpaces();
                $value = $this->getValue((string)$attribute);
                if ($prefix = $this->nsPrefix($namespaces)) {
                    $attName = $this->prefixKey($prefix, $attribute->getName());
                } else {
                    $attName  = $attribute->getName();
                }
                $attrs[$attName] = $value;
            }
            $attributes = array('@attributes' => $attrs);
        }

        $text = $this->prepareTextValue($xml, current($attributes));
        $result = $this->childNodesToArray($childpool, $parentName);

        if (!empty($attributes)) {
            if (!is_null($text)) {
                $result[$this->getTypeKey($text)] = $text;
            }
            $result = array_merge($attributes, $result);
            return $result;

        } else if (!is_null($text)) {
            if (!empty($result)) {
                $result[$this->getTypeKey($text)] = $text;
            } else {
                $result = $text;
            }
            return $result;
        }
        return (empty($result) && is_null($text)) ? null : $result;
    }

    /**
     * childNodesToArray
     *
     * @param array $children array containing SimpleXMLElements, most likely
     *  derived from an xpath query
     * @param string $parentName local-name of the parent node
     * @access public
     * @return array
     */
    public function childNodesToArray($children, $parentName = null, $nestedValues = false)
    {
        $result = array();
        foreach ($children as $child) {

            if (!$this->isSimpleXMLElement($child)) {
                throw new InvalidArgumentException(sprintf('The input array must only contain SimpleXMLElements but contains %s', gettype($child)));
            }

            $localNamespaces = $child->getNamespaces();
            $prefix = key($localNamespaces);
            $prefix = strlen($prefix) ? $prefix : null;
            $nsURL = current($localNamespaces);

            $name = $child->getName();
            $oname = $name;
            $name = is_null($prefix) ? $name : $this->prefixKey($prefix, $name);

            if (count($children) < 2) {
                $result[$name] = $this->parseXML($child, $nestedValues);
                break;
            }

            if (isset($result[$name])) {
                if (is_array($result[$name]) && array_is_numeric($result[$name])) {
                    $value = $this->parseXML($child, $nsURL, $prefix);
                    if (is_array($value) && array_is_numeric($value)) {
                        $result[$name] = array_merge($result[$name], $value);
                    } else {
                        $result[$name][] = $value;
                    }
                } else {
                    continue;
                }
            } else {

                $equals = $this->getEqualNodes($child, $prefix);

                if (count($equals) > 1) {
                    if ($this->isEqualOrPluralOf($parentName, $oname)) {
                        $result[] = $this->parseXML($child, $nestedValues);
                    } else {
                        $plural = $this->pluralize($oname);
                        $plural = is_null($prefix) ? $plural : $this->prefixKey($prefix, $plural);
                        if (isset($result[$plural]) && is_array($result[$plural])) {
                            $result[$plural][] = $this->parseXML($child, $nestedValues);
                        } elseif (count($children) !== count($equals)) {
                            $result[$plural][] = $this->parseXML($child, $nestedValues);
                        } else {
                            $result[$name][] = $this->parseXML($child, $nestedValues);
                        }
                    }
                } else {
                    $result[$name] = $this->parseXML($child, $nsURL, $nestedValues);
                }
            }
        }
        return $result;
    }

    /**
     * isSimpleXMLElement
     *
     * @param mixed $element
     * @access public
     * @return mixed
     */
    protected function isSimpleXMLElement($element)
    {
        return $element instanceof \SimpleXMLElement;
    }
/**
     * isEqualOrPluralOf
     *
     * @param mixed $name
     * @param mixed $singular
     * @access protected
     * @return boolean
     */
    protected function isEqualOrPluralOf($name, $singular)
    {
        return $name === $singular || $name === $this->pluralize($singular);
    }

    /**
     * getEqualNodes
     *
     * @param SimpleXMLElement $node
     * @param mixed $prefix
     * @access protected
     * @return array
     */
    protected function getEqualNodes(SimpleXMLElement $node, $prefix = null)
    {
        $name = is_null($prefix) ? $node->getName() : sprintf("%s:%s", $prefix, $node->getName());
        return $node->xpath(
            sprintf(".|following-sibling::*[name() = '%s']|preceding-sibling::*[name() = '%s']", $name, $name)
        );
    }
    /**
     * getEqualFollowingNodes
     *
     * @param SimpleXMLElement $node
     * @param mixed $prefix
     * @access protected
     * @return array
     */
    protected function getEqualFollowingNodes(SimpleXMLElement $node, $prefix = null)
    {
        $name = is_null($prefix) ? $node->getName() : sprintf("%s:%s", $prefix, $node->getName());
        return $node->xpath(
            sprintf(".|following-sibling::*[name() = '%s']", $name)
        );
    }

    /**
     * simpleXMLParentElement
     *
     * @param SimpleXMLElement $element
     * @param int $maxDepth
     * @access protected
     * @return boolean|SimpleXMLElement
     */
    protected function simpleXMLParentElement(SimpleXMLElement $element, $maxDepth = 4)
    {
        if (!$parent = current($element->xpath('parent::*'))) {
            $xpath = '';
            while ($maxDepth--) {
                $xpath .= '../';
                $query = sprintf('%sparent::*', $xpath);

                if ($parent = current($element->xpath($query))) {
                    return $parent;
                }

            }
        }
        return $parent;
    }

    /**
     * prefixKey
     *
     * @param mixed $prefix
     * @param mixed $localName
     * @access protected
     * @return mixed
     */
    protected function prefixKey($prefix, $localName)
    {
        if (!$this->checkPrefixes) {
            return $localName;
        }
        return sprintf('%s%s%s', $prefix, $this->prefixSeparator, $localName);
    }

    /**
     * nsPrefix
     *
     * @param array $namespaces
     * @access protected
     * @return mixed
     */
    protected function nsPrefix(array $namespaces)
    {
        $prefix = key($namespaces);
        return strlen($prefix) ? $prefix : null;
    }
    /**
     * convert boolish and numeric values
     *
     * @param mixed $text
     * @param array $attributes
     */
    protected function prepareTextValue(SimpleXMLElement $xml, $attributes = null)
    {
        return (isset($attributes['type']) && 'text' === $attributes['type']) ? clear_value((string)$xml) : $this->getValue((string)$xml);
    }

    /**
     * determine the array key name for textnodes with attributes
     *
     * @param mixed|string $value
     */
    protected function getTypeKey($value)
    {
        //return is_string($value) ? 'text' : 'value';
        return $this->nodeValueKey;
    }
}
