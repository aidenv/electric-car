<?php

namespace Yilinker\Bundle\CoreBundle\Twig;

use Yilinker\Bundle\CoreBundle\Twig\CustomLexer;

class YilinkerTwigEnvironment extends \Twig_Environment
{
    public function tokenize($source, $name = null)
    {
        $source = $this->insertTranslationSnippets($source);
        return parent::tokenize($source, $name);
    }

    public function insertTranslationSnippets($code)
    {
        // between tags
        $regex = '/(\<([a-z][a-zA-Z0-9]*)\b[^>]*\>)([^{.]+?)([\n]*\<\/\2\>)/';

        $code = preg_replace_callback($regex, function($matches) {
            $str = array_shift($matches);

            return preg_replace('/\>([^\}\^>\n]+)\</', '> {% trans %} ${1} {% endtrans %} <', $str);
        }, $code);

        // attribute values
        $attrNames = array('placeholder', 'title');
        $attrs = implode('|', $attrNames);

        $code = preg_replace(
            '/(('.$attrs.')=")([^"^{]+)(?=")/',
            '${1}{% trans %} ${3} {% endtrans %}',
            $code
        );
        
        return $code;
    }
}