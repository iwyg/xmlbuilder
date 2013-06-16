<?php

/**
 * This File is part of the ..\Serializer package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder\Dom;

use InvalidArgumentException;

/**
 * Class: SimpleXMLElement
 *
 * @uses \SimpleXMLElement
 *
 * @package
 * @version
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class SimpleXMLElement extends \SimpleXMLElement
{
    /**
     * boolish
     *
     * @var array
     */
    public static $boolish = ['yes', 'no', 'true', 'false'];

    /**
     * argumentsAsArray
     *
     * @access public
     * @return array
     */
    public function attributesAsArray($namespace = null)
    {
        $attributes = [];
        $attr = $this->xpath('./@*');

        foreach ($attr as $key => $value) {
            $attributes[$key] = $this->getPhpValue((string)$value);
        }
        return $attributes;
    }

    /**
     * phpValue
     *
     * @access public
     * @return mixed
     */
    public function phpValue()
    {
        return $this->getPhpValue((string)$this);
    }

    /**
     * addCDATASection
     *
     * @param mixed $content string, SimpleXMLElement, or DOMDocument
     * @access public
     * @return void
     */
    public function addCDATASection($content)
    {
        switch (true) {
            case is_string($content):
                break;
            case ($content instanceof \SimpleXMLElement):
                $dom = dom_import_simplexml($content);
                $content = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                break;
            case ($content instanceof \DOMDocument):
                $content = $content->saveXML($content->childNodes->item(0));
                break;
            case ($content instanceof \DOMNode):
                $dom = new DOMDocument();
                $dom->appendChild($content);
                $content = $dom->saveXML($dom->childNodes->item(0));
                break;
            default:
                throw new InvalidArgumentException(
                    'expected arguement 1 to be String, SimpleXMLElement, DOMNode, or DOMDocument, instead saw ' . gettype($content)
                );
        }

        $import = dom_import_simplexml($this);
        $node   = $import->ownerDocument;
        $import->appendChild($node->createCDATASection($content));
    }

    /**
     * Append a childelement from a well formed html string.
     *
     * @param string a html string
     * @access public
     * @return void
     */
    public function appendChildFromHtmlString($html)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html);

        $element = simplexml_import_dom($dom);
        $element = current($element->xPath('//body/*'));

        $this->appendChildNode($element);
    }

    /**
     * Append a childelement from a well formed xml string.
     *
     * @param string $xml a well formed xml string
     * @access public
     * @return void
     */
    public function appendChildFromXmlString($xml)
    {
        $dom = new DOMDocument;
        $dom->loadXML($xml);

        $element = simplexml_import_dom($dom);

        $this->appendChildNode($element);
    }

    /**
     * Append a SimpleXMLElement to the current SimpleXMLElement.
     *
     * @param \SimpleXMLElement $element the element to be appended.
     *
     * @access public
     * @return void
     */
    public function appendChildNode(\SimpleXMLElement $element)
    {
        $target  = dom_import_simplexml($this);
        $insert = $target->ownerDocument->importNode(dom_import_simplexml($element), true);
        $target->appendChild($insert);
    }

    /**
     * phpValue
     *
     * @param mixed $param
     * @access public
     * @return mixed
     */
    protected function getPhpValue($value)
    {
        switch (true) {
        case is_bool($value):
        return $value ? 'true' : 'false';
        case is_numeric($value):
            return ctype_digit($value) ? intval($value) : floatval($value);
        case in_array($value, static::$boolish):
            return ('false' === $value || 'no' === $value) ? false : true;
        default:
            return clear_value(trim($value));
        };

    }
}
