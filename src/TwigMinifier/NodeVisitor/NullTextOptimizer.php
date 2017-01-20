<?php

namespace TwigMinifier\NodeVisitor;

use TwigMinifier\Node\NullNode;

/**
 * @author Linnik Sergey <linniksa@gmail.com>
 */
class NullTextOptimizer implements \Twig_NodeVisitorInterface
{

    public function enterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        /** @var \Twig_Node $node */
        if ($node instanceof \Twig_Node_Text && '' == $node->getAttribute('data')) {

            return new NullNode();
        }

        if ($node instanceof \Twig_Node_Print) {
            $expr = $node->getNode('expr');

            /** @var \Twig_Node $expr */
            if ($expr instanceof \Twig_Node_Expression_Constant) {
                if ('' == $expr->getAttribute('value')) {

                    return new NullNode();
                }
            }
        }

        return $node;
    }

    public function leaveNode(\Twig_Node $node, \Twig_Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 1024;
    }

}
