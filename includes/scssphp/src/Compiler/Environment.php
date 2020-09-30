<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace OM4\Vendor\ScssPhp\ScssPhp\Compiler;

/**
 * Compiler environment
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Environment
{
    /**
     * @var \OM4\Vendor\ScssPhp\ScssPhp\Block
     */
    public $block;
    /**
     * @var \OM4\Vendor\ScssPhp\ScssPhp\Compiler\Environment
     */
    public $parent;
    /**
     * @var array
     */
    public $store;
    /**
     * @var array
     */
    public $storeUnreduced;
    /**
     * @var integer
     */
    public $depth;
}
