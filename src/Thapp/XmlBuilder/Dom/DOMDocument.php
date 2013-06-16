<?php

/**
 * This File is part of the Selene\Xml package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder\Dom;

use DOMXpath;
use DOMDocument as BaseDOM;

/**
 * @class DOMDocument
 * @package
 * @version $Id$
 */
class DOMDocument extends BaseDom
{
    /**
     * xpath
     *
     * @var DOMXpath
     */
    protected $xpath;

    /**
     * __construct
     *
     * @param mixed $version
     * @param mixed $encoding
     * @access public
     * @return mixed
     */
    public function __construct($version = null, $encoding = null)
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMElement', __NAMESPACE__ . '\\DOMElement');
    }

    /**
     * xPath
     *
     * @param mixed $query
     * @access public
     * @return DOMNodeList
     */
    public function xPath($query, DOMNode $contextNode = null)
    {
        return $this->getXpath()->query($query, $contextNode);
    }

    /**
     * importElement
     *
     * @param DOMElement $import
     * @param DOMElement $element
     * @access public
     * @return mixed
     */
    public function appendDomElement(DOMElement $import, DOMElement $element = null, $deep = true)
    {
        $import = $this->importNode($import, $deep);

        if (is_null($element)) {
            return $this->firstChild->appendChild($import);
        }

        return $element->appendChild($import);
    }

    /**
     * getXpath
     *
     * @access protected
     * @return DOMXpath
     */
    public function getXpath()
    {
        if (!$this->xpath) {
            $this->xpath = new DOMXpath($this);
        }
        return $this->xpath;
    }

}

