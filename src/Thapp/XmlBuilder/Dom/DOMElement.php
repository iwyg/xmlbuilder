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

use DOMElement as BaseDOMElement;

/**
 * @class DOMElement
 * @package
 * @version $Id$
 */
class DOMElement extends BaseDOMElement
{
    /**
     * xPath
     *
     * @access public
     * @return mixed
     */
    public function xPath($query)
    {
        return $this->ownerDocument->getXpath($query, $this);
    }

    /**
     * appendDomElement
     *
     * @param DOMElement $import
     * @access public
     * @return mixed
     */
    public function appendDomElement(DOMElement $import, $deep = true)
    {
        return $this->ownerDocument->appendDomElement($import, $this, $deep);
    }

}
