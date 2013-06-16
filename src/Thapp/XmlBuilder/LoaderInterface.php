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

/**
 * @class LoaderInterface
 */

interface LoaderInterface
{
    /**
     * create a new loader instance
     */
    public function create();

    /**
     * load a file.
     * @param $file string
     */
    public function load($file);

    /**
     * getErrors
     *
     * @access public
     * @return array
     */
    public function getErrors();

    /**
     * set a loader Option
     *
     * @param mixed $option
     * @param mixed $value
     * @access public
     * @return void
     */
    public function setOption($option, $value);

    /**
     * get a loader Option
     *
     * @param mixed $option
     * @param mixed $default
     * @access public
     * @return mixed
     */
    public function getOption($option = null, $default = null);
}

