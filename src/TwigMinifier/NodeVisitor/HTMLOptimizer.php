<?php

namespace TwigMinifier\NodeVisitor;

/**
 * @author  Linnik Sergey <linniksa@gmail.com>
 */
class HTMLOptimizer implements \Twig_NodeVisitorInterface
{
    protected $tree = array();
    protected $lastLeavedNode;
    protected $lastEnterNode;

    protected $inHead = false;

    protected $type = null;

    protected function hasBlockInTree() {
        foreach ($this->tree as $node) {
            if ($node instanceof \Twig_Node_Block) {
                return $node;
            }
        }
        return false;
    }

    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            /** @var \Twig_Node_Module $node_module  */
            $node_module = $node;
            $filename = $node_module->getAttribute('filename');

            $this->inHead = false;
            $this->type = null;

            if (preg_match('#\.(html|xml)\.twig$#iu', $filename, $matches)) {
                $this->type = strtolower($matches[1]);
            }
        }

        if (('html' == $this->type)) {

            if ($node instanceof \Twig_Node_Text) {
                /** @var \Twig_Node_Text $node_text */
                $node_text = $node;
                $nodeText = $node_text->getAttribute('data');

                $nodeText = preg_replace('#<!--[^\[><](.+?)-->#um', '', $nodeText);

                $nodeText = preg_replace_callback('#[\s\t]+#ium', function($matches) {
                    foreach ($matches as $m) {
                        if (preg_match('#[\n\r]+#ium', $m)) {
                            return "\n";
                        } else {
                            return ' ';
                        }
                    }
                }, $nodeText);

                $trimLeft = false;
                $trimRight = false;

                // если мы в блоке и это первый текстовый блок
                /** @var \Twig_Node_Block $block  */
                if (($block = $this->hasBlockInTree())) {
                    $block_body = $block->getNode('body');
                    if ($block_body == $node || ( $block_body->hasNode(0) && $block_body->getNode(0) == $node )) {
                        $trimLeft = true;
                    }
                }

                // срезаем пробелы после блока
                if (
                    $this->lastLeavedNode instanceof \Twig_Node_Set
                ) {
                    $trimLeft = true;
                }

                if ($trimLeft) {
                    $nodeText = ltrim($nodeText);
                }

                if ($trimRight) {
                    $nodeText = rtrim($nodeText);
                }

                if (preg_match('#<head>#ium', $nodeText)) {
                    $this->inHead = true;
                }

                if (preg_match('#<(body|/head)>#ium', $nodeText)) {
                    $this->inHead = false;
                }

                if ($this->inHead) {
                    $nodeText = preg_replace('#>\s+<#ium','><', $nodeText);
                    $nodeText = preg_replace('#>\s+#ium','>', $nodeText);
                    $nodeText = preg_replace('#\s+<#ium','<', $nodeText);
                    $nodeText = preg_replace('#\s*</title>\s*#ium','</title>', $nodeText);
                }

                $stripAllSpaces = join('|', array(
                    'iframe|a|br|p|div|option|noscript|script|style|body|html|head|title|meta|link|form|tr|td|th|table|tbody|tfoot|thead',
                    'switch|g|polygon|path|foreignObject', //svg
                    'li', // test
                ));

                $stripInnerSpaces = join('|', array(
                    'svg', 'ul'
                ));

                // некоторые ручные оптимизации
                $nodeText = preg_replace('#</head>\s+<body\s+#ium','</head><body ', $nodeText);
                $nodeText = preg_replace('#(</[^>]+>)\s+(</[^>]+>)#ium','$1$2', $nodeText);

                $nodeText = preg_replace('#\s*(</?('. $stripAllSpaces . ')[^>]*>)\s*#ium','$1', $nodeText);
                $nodeText = preg_replace('#(<('. $stripInnerSpaces . ')[^>]*>)\s+#ium','$1', $nodeText);
                $nodeText = preg_replace('#\s+(</('. $stripInnerSpaces . ')[^>]*>)#ium','$1', $nodeText);

                $nodeText = preg_replace('#(\s+)(</?[^>]*>)\s+#ium','$1$2', $nodeText);

                // оптимизация кавычек
                //$nodeText = $this->optimizeQuotes($nodeText);

                // нормализации окончаний тегов
                $nodeText = preg_replace('#[\s]+>#imu', '>', $nodeText);
                $nodeText = preg_replace('#[\s]*/>#imu', ' />', $nodeText);

                $nodeText = preg_replace('#" />#imu', '"/>', $nodeText);

                $node_text->setAttribute('data', $nodeText);
            }

            if ($node instanceof \Twig_Node_Set && $this->lastLeavedNode instanceof \Twig_Node_Text) {
                $data = $this->lastLeavedNode->getAttribute('data');
                $data = preg_replace('#\s+$#', '', $data);
                $this->lastLeavedNode->setAttribute('data', $data);
            }

        }

        array_unshift($this->tree, $node);

        return $this->lastEnterNode = $node;
    }

    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if (isset($this->tree[0]) && $this->tree[0] == $node) {
            array_shift($this->tree);
        }

        return $this->lastLeavedNode = $node;
    }

    public function getPriority()
    {
        return 1000;
    }

    /**
     * @param $nodeText
     *
     * @return mixed
     */
    protected function optimizeQuotes($nodeText)
    {
        // TODO: добавить и одинарные кавычки
        if ('html' == $this->type) {
            $nodeText = preg_replace_callback(
                '#(?<name>[^\s"=]+?)="(?<val>[^"]*?)"#ium',
                function ($matches) {
                    $name = strtolower($matches['name']);
                    $val  = $matches['val'];

                    switch ($name) {
                        case 'class':
                            $val = trim(preg_replace('#\s+#ium', ' ', $val));
                            break;
                    }

                    if (
                        preg_match('#[\s"\'=]+#', $val) or
                        preg_match('#/$#', $val) or // чтобы ссылки с оконечным слешом не обрезались
                        0 == strlen(trim($val)) // чтобы не ломать верстку
                    ) {
                        $result = sprintf('%s="%s"', $name, $val);
                    } else {
                        $result = sprintf('%s=%s', $name, $val);
                    }

                    return $result;
                },
                $nodeText
            );

            return $nodeText;
        }

        return $nodeText;
    }

}
