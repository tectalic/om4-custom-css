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
namespace OM4\Vendor\ScssPhp\ScssPhp\Block;

use OM4\Vendor\ScssPhp\ScssPhp\Block;
use OM4\Vendor\ScssPhp\ScssPhp\Compiler\Environment;
use OM4\Vendor\ScssPhp\ScssPhp\Node\Number;
/**
 * @internal
 */
class CallableBlock extends Block
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var list<array{string, array|Number|null, bool}>|null
     */
    public $args;
    /**
     * @var Environment|null
     */
    public $parentEnv;
    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }
}
