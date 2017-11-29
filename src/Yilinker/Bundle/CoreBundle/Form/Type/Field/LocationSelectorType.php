<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Field;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Yilinker\Bundle\CoreBundle\Form\DataTransformer\EntityToPrimaryTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

class LocationSelectorType extends IntegerType
{
    private $em;
    private $assetHelper;
    private $dispatcher;

    public function setEM($em)
    {
        $this->em = $em;
    }

    public function setAssetHelper($assetHelper)
    {
        $this->assetHelper = $assetHelper;
    }

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityTransformer = new EntityToPrimaryTransformer($this->em, 'YilinkerCoreBundle:Location');

        $builder->addModelTransformer($entityTransformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $scale = function (Options $options) {
            if (null !== $options['precision']) {
                @trigger_error('The form option "precision" is deprecated since version 2.7 and will be removed in 3.0. Use "scale" instead.', E_USER_DEPRECATED);
            }

            return $options['precision'];
        };

        $resolver->setDefaults(array(
            'precision' => null,
            'scale' => $scale,
            'grouping' => false,
            'rounding_mode' => IntegerToLocalizedStringTransformer::ROUND_DOWN,
            'compound' => false,
            'include_js' => true,
            'attr' => array(
                'style' => 'display:none',
                'data-location-selector' => ''
            ),
            'view' => 'YilinkerCoreBundle:Field:location_selector.html.twig',
            'required' => false,
            'locationQueue' => array(
                array(
                    'locationId' => 0,
                    'locationTypeId' => 6
                ),
                array(
                    'locationTypeId' => array(4, 5)
                ),
                array(
                    'locationTypeId' => array(7)
                )
            )
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$options['include_js']) {
            return;
        }

        $js = $this->assetHelper->assetHelper('js/src/utility/location-selector.js');

        $this->dispatcher->addListener('kernel.response', function($event) use ($js, $options) {
            $response = $event->getResponse();
            $content = $response->getContent();
            $pos = stripos($content, '</body>');

            if ($pos > -1) {
                $jsContent = '<script src="'.$js.'"></script>';
                $jsSettings = 
                    '<script>
                        var $locationSelector = $("[data-location-selector]");
                        $locationSelector.locationSelector({
                            view: "'.$options['view'].'",
                            locationQueue: '.json_encode($options['locationQueue']).'
                        });
                    </script>'
                ;

                $content = substr($content, 0, $pos).$jsContent.$jsSettings.substr($content, $pos);
                $response->setContent($content);
                $event->setResponse($response);
            }
        });
    }

    public function getName()
    {
        return 'location_selector';
    }
}