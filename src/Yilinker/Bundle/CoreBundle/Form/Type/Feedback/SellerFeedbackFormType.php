<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Feedback;

use Yilinker\Bundle\CoreBundle\Form\DataTransformer\EntityToPrimaryTransformer;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\CoreBundle\Entity\FeedbackType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class SellerFeedbackFormType extends YilinkerBaseFormType
{
    private $container;
    private $em;

    public function __construct($container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('reviewee', 'hidden');
        $builder->add('order', 'hidden');

        $builder->add('rating'.FeedbackType::FEEDBACK_TYPE_COMMUNICATION, 'hidden', array(
            'mapped' => false
        ));
        $builder->add('rating'.FeedbackType::FEEDBACK_TYPE_QUALITY, 'hidden', array(
            'mapped' => false
        ));

        $builder->add('title', 'text', array(
            'constraints' => array(
                new Length(array(
                    'max' => 255,
                    'maxMessage' => 'Review title can only be up to {{ limit }} characters',
                ))
            )
        ));
        $builder->add('feedback', 'textarea', array(
            'constraints' => array(
                new Length(array(
                    'max' => 1024,
                    'maxMessage' => 'Feedback content can only be up to {{ limit }} characters',
                ))
            )
        ));
        $this->addEventListeners($builder);
        $this->addFieldTransformers($builder);
    }

    public function addFieldTransformers($builder)
    {
        $store = new EntityToPrimaryTransformer($this->em, 'YilinkerCoreBundle:Store');
        $userOrder = new EntityToPrimaryTransformer($this->em, 'YilinkerCoreBundle:UserOrder');

        $builder->get('reviewee')->addModelTransformer($store);
        $builder->get('order')->addModelTransformer($userOrder);
    }

    public function addEventListeners($builder)
    {
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) {
            $userFeedback = $event->getData();
            if (!$userFeedback) {
                return;
            }
            $form = $event->getForm();

            $feedbackType = $this->em->getReference('YilinkerCoreBundle:FeedbackType', FeedbackType::FEEDBACK_TYPE_COMMUNICATION);
            $rating = $form['rating'.FeedbackType::FEEDBACK_TYPE_COMMUNICATION]->getData();
            $userFeedbackRating = new UserFeedbackRating;
            $userFeedbackRating->setType($feedbackType);
            $userFeedbackRating->setFeedbacks($userFeedback);
            $userFeedbackRating->setRating($rating);
            $userFeedback->addRating($userFeedbackRating);
            $totalRating = intval($rating);

            $feedbackType = $this->em->getReference('YilinkerCoreBundle:FeedbackType', FeedbackType::FEEDBACK_TYPE_QUALITY);
            $rating = $form['rating'.FeedbackType::FEEDBACK_TYPE_QUALITY]->getData();
            $userFeedbackRating = new UserFeedbackRating;
            $userFeedbackRating->setType($feedbackType);
            $userFeedbackRating->setFeedbacks($userFeedback);
            $userFeedbackRating->setRating($rating);
            $userFeedback->addRating($userFeedbackRating);
            $totalRating += intval($rating);

            $rating = $totalRating / 2;
            $userFeedback->setRating($rating);

            $user = $this->container
                         ->get('security.token_storage')
                         ->getToken()
                         ->getUser()
            ;
            $user = $user instanceof User ? $user: null;
            $userFeedback->setReviewer($user);
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\UserFeedback'
        ));
    }

    public function getName()
    {
        return 'seller_feedback';
    }
}