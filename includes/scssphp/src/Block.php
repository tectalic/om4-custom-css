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
namespace OM4\Vendor\ScssPhp\ScssPhp;

/**
 * Block
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Block
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var \OM4\Vendor\ScssPhp\ScssPhp\Block
     */
    public $parent;
    /**
     * @var string
     */
    public $sourceName;
    /**
     * @var integer
     */
    public $sourceIndex;
    /**
     * @var integer
     */
    public $sourceLine;
    /**
     * @var integer
     */
    public $sourceColumn;
    /**
     * @var array
     */
    public $selectors;
    /**
     * @var array
     */
    public $comments;
    /**
     * @var array
     */
    public $children;
    /**
     * @var \OM4\Vendor\ScssPhp\ScssPhp\Block
     */
    public $selfParent;
}
