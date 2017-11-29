<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class YilinkerBaseFormType extends AbstractType
{
    /**
     * Default options
     * @var array
     */
    private $defaultOptions;

    public function __construct($defaultOptions = [])
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults($this->defaultOptions);
    }

    /**
     * Add new value to $defaultOptions
     * @param array $additionalOptions
     */
    public function addDefaultOptions($additionalOptions)
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $additionalOptions);
    }

    /**
     * Return $defaultOptions
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * Must implement Symfony\Component\Form\FormTypeInterface::getName
     * to avoid error AbstractType implementation
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
