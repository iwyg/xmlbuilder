<?php

/**
 * This File is part of the Selene\Compiler package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Thapp\XmlBuilder;

use Thapp\XmlBuilder\Dom\DOMDocument;
use Thapp\XmlBuilder\Dom\SimpleXMLElement;

/**
 * Class: XmlLoader
 *
 * @implements LoaderInterface
 *
 * @package
 * @version
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class XmlLoader implements LoaderInterface
{
    /**
     * options
     *
     * @var array
     */
    protected $options = array();

    /**
     * errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * xmlErrors
     *
     * @var array
     */
    protected $xmlErrors = array();

    /**
     * setOption
     *
     * @param mixed $option
     * @param mixed $value
     * @access public
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * getOption
     *
     * @param mixed $option
     * @param mixed $default
     * @access public
     * @return mixed
     */
    public function getOption($option = null, $default = null)
    {
        if (!is_null($default) and !isset($this->options[$option])) {
            return $default;
        }
        return is_null($option) ? $this->options : $this->options[$option];
    }

    public function load($file)
    {
        $xml = $this->doLoad($file);

        if ($errors = $this->getErrors()) {
            throw new \Exception($this->formatErrors($errors, $file));
        }
        return $xml;
    }

    protected function formatErrors(array $errors, $file) {
        $output = "[file] $file \n";

        foreach ($errors as $errnum => $error) {
            $output .= "[$errnum] $error \n";
        }

        return $output;
    }

    /**
     * load
     *
     * @param mixed $file
     * @access public
     * @return mixed
     */
    protected function doLoad($file)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $load = ($fromString = $this->getOption('from_string', false)) ? 'loadXML' : 'load';
        $file = $fromString ? $file : realpath($file);

        if (!$this->loadXmlInDom($dom, $file, $load)) {
            return false;
        }

        $simpleXML = $this->getOption('simplexml', false);

        if ($simpleXML) {
            $xml = simplexml_import_dom($dom, __NAMESPACE__ . '\\SimpleXMLElement');
            return $xml;
        }

        return $dom;
    }

    /**
     * getErrors
     *
     * @access public
     * @return mixed|bool|array
     */
    public function getErrors()
    {
        return $this->getAllErrors();
    }

    /**
     * create
     *
     * @access public
     * @return static
     */
    public function create()
    {
        return new static();
    }

    /**
     * __clone
     *
     * @access public
     * @return mixed
     */
    public function __clone()
    {
        $this->options = clone $this->options;
    }

    /**
     * loadXmlInDom
     *
     * @param \DOMDocument $dom
     * @param mixed $file
     * @param string $load
     * @access protected
     * @return DOMDocument;
     */
    protected function loadXmlInDom(\DOMDocument $dom, $file, $load = 'load')
    {
        $errored = false;

        $usedInternalErrors = libxml_use_internal_errors(true);
        $externalEntitiesDisabled = libxml_disable_entity_loader(false);
        libxml_clear_errors();

        set_error_handler(array($this, 'handleXMLErrors'));

        // LIBXML_NONET prevents local and remote file inclusion attacks
        try {
            call_user_func_array(array($dom, $load), array($file, LIBXML_NONET | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0));
        } catch (\Exception $e) {
            $this->errors[] = trim($e->getMessage(), "\n");
            return false;
        }

        restore_error_handler();

        if ($errors = libxml_get_errors()) {
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

        return $dom;
    }

    /**
     * handleXMLErrors
     *
     * @param mixed $errorno
     * @param mixed $errstr
     * @access public
     * @return mixed
     */
    public function handleXMLErrors($errorno, $errstr)
    {
        $this->xmlErrors = libxml_get_errors();

        if (0 === error_reporting()) {
            return false;
        }

        $this->errors[] = trim($errstr, "\n");
    }

    /**
     * getXmlErrors
     *
     * @access private
     * @return mixed
     */
    private function getAllErrors()
    {
        $errors = array();

        foreach ($this->xmlErrors as $error) {
            $errors[] = trim($error->message, "\n");
        }

        $errors = array_merge($this->errors, $errors);
        return empty($errors) ? false : $errors;
    }
}

