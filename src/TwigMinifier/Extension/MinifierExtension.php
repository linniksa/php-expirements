<?php

namespace TwigMinifier\Extension;

use TwigMinifier\NodeVisitor\HTMLOptimizer;
use TwigMinifier\NodeVisitor\SpacelessOptimizer;
use TwigMinifier\NodeVisitor\NullTextOptimizer;

/**
 * @author Linnik Sergey <linniksa@gmail.com>
 */
class MinifierExtension extends \Twig_Extension
{

    public function getNodeVisitors()
    {
        return array(
            new SpacelessOptimizer(),
            new HTMLOptimizer(),
            new NullTextOptimizer(),
        );
    }

    public function getName()
    {
        return 'pure';
    }
}
