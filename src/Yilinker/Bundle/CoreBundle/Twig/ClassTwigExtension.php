<?php

namespace Yilinker\Bundle\CoreBundle\Twig;

use Twig_Extension;
use Twig_Function_Method;

/**
 * Class Reflection Twig Extension
 */
class ClassTwigExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'class' => new \Twig_SimpleFunction('class', array($this, 'getClass'))
        );
    }

    public function getName()
    {
        return 'class_twig_extension';
    }

    public function getClass($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }
}

