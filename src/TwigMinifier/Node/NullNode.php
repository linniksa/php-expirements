<?php

namespace TwigMinifier\Node;

/**
 * @author  Linnik Sergey <linniksa@gmail.com>
 */

class NullNode extends \Twig_Node
{

    public function compile(\Twig_Compiler $compiler)
    {
        // nothing to compile
    }

}
