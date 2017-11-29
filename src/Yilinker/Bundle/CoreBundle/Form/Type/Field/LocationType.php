<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Field;

use Yilinker\Bundle\CoreBundle\Form\DataTransformer\EntityToPrimaryTransformer;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Yilinker\Bundle\CoreBundle\Entity\LocationType as EntityLocationType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class LocationType extends AbstractType
{
    private $assetHelper;
    private $dispatcher;
    private $em;
    private $locationService;

    public function __construct($assetHelper, $dispatcher, $em, $locationService)
    {
        $this->assetHelper = $assetHelper;
        $this->dispatcher = $dispatcher;
        $this->em = $em;
        $this->locationService = $locationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEventListeners($builder, $options);
        $entityTransformer = new EntityToPrimaryTransformer($this->em, 'YilinkerCoreBundle:Location', false, false);
        $builder->addModelTransformer($entityTransformer);
    }

    public function addEventListeners($builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use($options) {
                $form = $event->getForm();
                $location = $event->getData();

                $barangayChoices = array();
                $cityOrMunicipalityChoices = array();
                $provinceChoices = array();

                if ($location) {
                    $locationTypeId = $location->getLocationType()->getLocationTypeId();

                    if ($locationTypeId == EntityLocationType::LOCATION_TYPE_BARANGAY) {
                        $barangay = $location;
                        $cityOrMunicipality = $barangay->getParent();
                        $province = $cityOrMunicipality->getParent();
                    }
                    elseif ($locationTypeId == EntityLocationType::LOCATION_TYPE_CITY
                        || $locationTypeId == EntityLocationType::LOCATION_TYPE_MUNICIPALITY) {
                        $cityOrMunicipality = $location;
                        $province = $cityOrMunicipality->getParent();
                    }
                    elseif ($locationTypeId == EntityLocationType::LOCATION_TYPE_PROVINCE) {
                        $province = $location;
                    }

                    if (isset($cityOrMunicipality)) {
                        $barangayChoices = $this->locationService->getSimplifiedChildren(
                            $cityOrMunicipality->getLocationId(), 
                            EntityLocationType::LOCATION_TYPE_BARANGAY,
                            true
                        );
                    }

                    $cityOrMunicipalityChoices = $this->locationService->getSimplifiedChildren(
                        $province->getLocationId(),
                        $cityOrMunicipality->getLocationType()->getLocationTypeId(),
                        true
                    );

                    $provinceChoices = $this->locationService->getSimplifiedChildren(
                        $province->getParent()->getLocationId(), 
                        EntityLocationType::LOCATION_TYPE_PROVINCE,
                        true
                    );

                    $country = $province->getParent();
                }

                $provinceOptions = array(
                    'mapped'        => false,
                    'class'         => 'YilinkerCoreBundle:Location',
                    'query_builder' => function ($er) {
                        $qb = $er->createLocationsByTypeQB(EntityLocationType::LOCATION_TYPE_PROVINCE, true);

                        return $qb;
                    },
                    'placeholder' => 'Choose a Province',
                    'data'        => isset($province) ? $province: null
                );

                if ($options['include_country']) {
                    $form->add('country', 'entity', array(
                        'mapped'        => false,
                        'class'         => 'YilinkerCoreBundle:Location',
                        'query_builder' => function ($er) {
                            $qb = $er->createLocationsByTypeQB(EntityLocationType::LOCATION_TYPE_COUNTRY, true);

                            return $qb;
                        },
                        'placeholder' => 'Choose a Country',
                        'data'        => isset($country) ? $country: null
                    ));

                    $provinceOptions['choices'] = $provinceChoices;
                    unset($provinceOptions['query_builder']);
                }

                $form->add('province', 'entity', $provinceOptions);

                $form->add('cityOrMunicipality', 'entity', array(
                    'mapped'        => false,
                    'class'         => 'YilinkerCoreBundle:Location',
                    'choices'       => $cityOrMunicipalityChoices,
                    'placeholder'   => 'Choose a City or Municipality',
                    'label'         => 'City or Municipality',
                    'data'          => isset($cityOrMunicipality) ? $cityOrMunicipality: null
                ));   

                $form->add('barangay', 'entity', array(
                    'label'         => 'Barangay or District',
                    'mapped'        => false,
                    'class'         => 'YilinkerCoreBundle:Location',
                    'choices'       => $barangayChoices,
                    'placeholder'   => 'Choose a Barangay',
                    'data'          => isset($barangay) ? $barangay: null
                ));

            }
        );
        
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function($event) {
            $form = $event->getForm();
            $data = $event->getData();
            $locationId = 0;
            if (is_array($data)) {
                if (array_key_exists('barangay', $data) && $data['barangay']) {
                    $locationId = $data['barangay'];
                }
                elseif (array_key_exists('cityOrMunicipality', $data) && $data['cityOrMunicipality']) {
                    $locationId = $data['cityOrMunicipality'];
                }
                elseif (array_key_exists('province', $data) && $data['province']) {
                    $locationId = $data['province'];
                }
            }
            elseif ($data) {
                $locationId = $data;
            }
            $form->locationId = $locationId;
            $event->setData(array());
        });

        $builder->addEventListener(FormEvents::SUBMIT, function($event) {
            $form = $event->getForm();
            $locationId = $form->locationId;
            if ($locationId) {
                $tbLocation = $this->em->getRepository('YilinkerCoreBundle:Location');
                $location = $tbLocation->find($locationId);
                $event->setData($location);
            }      
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$options['include_location_js']) {
            return;
        }

        $locationJs = $this->assetHelper->assetHelper('js/src/utility/location.js');

        $this->dispatcher->addListener('kernel.response', function($event) use ($locationJs) {
            $response = $event->getResponse();
            $content = $response->getContent();
            $pos = stripos($content, '</body>');

            if ($pos > -1) {
                $javascriptContent = '<script src="'.$locationJs.'"></script>';
                $content = substr($content, 0, $pos).$javascriptContent.substr($content, $pos);
                $response->setContent($content);
                $event->setResponse($response);
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'include_location_js' => true,
            'include_country' => false
        ));
    }

    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'location';
    }
}