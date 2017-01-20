<?php

namespace TwigMinifier\NodeVisitor;

/**
 * @author  Linnik Sergey <linniksa@gmail.com>
 */
class SpacelessOptimizer implements \Twig_NodeVisitorInterface
{
    protected $spaceLessBlocks;

    public function enterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            $this->spaceLessBlocks =  array();
        }

        if (count($this->spaceLessBlocks) && $node instanceof \Twig_Node_Text) {
            $nodeText = $node->getAttribute('data');
            $nodeText = preg_replace('#>\s+<#', '><', $nodeText);

            $nodeText = preg_replace_callback('#[\s\t]{2,}#ium', function($m) {
                return preg_match('#[\n\r]#ium', $m[0]) ? "\n" : ' ';
            }, $nodeText);

            $node->setAttribute('data', $nodeText);
        }

        if ($node instanceof \Twig_Node_Spaceless) {
            array_unshift($this->spaceLessBlocks, $node);
        }

        return $node;
    }

    public function leaveNode(\Twig_Node $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Spaceless) {
            array_shift($this->spaceLessBlocks);
        }

        return $node;
    }

    public function getPriority()
    {
        return 128;
    }

}
