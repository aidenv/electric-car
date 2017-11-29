<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\MobileFeedBackAdmin;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class FeedbackController
 * @Security("has_role('ROLE_ADMIN')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class FeedbackController extends Controller
{
    public function indexAction (Request $request)
    {   
        $params['page'] = $request->get('page',1);

        $em = $this->getDoctrine()->getManager();
        $list = $em->getRepository('YilinkerCoreBundle:MobileFeedBack')->getList($params);

        return $this->render('YilinkerBackendBundle:Feedback:feedback_list.html.twig', array('list' => $list));
    }

    public function detailsAction(Request $request)
    {
        $this->em = $this->getDoctrine()->getManager();

        $mobilefeedbackId = $request->get('id');

        $mobilefeedback = $this->em->getRepository('YilinkerCoreBundle:MobileFeedBack')
                                ->findOneBy(array('mobileFeedbackId' => $mobilefeedbackId));

        $this->feedBackRead(array('mobileFeedback' => $mobilefeedback));

        $template = $this->renderView('YilinkerBackendBundle:Feedback:feedback_detail.html.twig',
                            array('mobilefeedback' => $mobilefeedback
                        ));
        
        return new Response($template);
    }

    protected function feedBackRead($params=array())
    {
        $mobilefeedbackId = $params['mobileFeedback']->getMobileFeedbackId();

        $mobileFeedbackAdmin = $this->em->getRepository('YilinkerCoreBundle:MobileFeedBackAdmin')
                                    ->findOneBy(array(
                                        'adminUser'      => $this->getUser()->getAdminUserId(),
                                        'mobileFeedback' => $mobilefeedbackId
                                    ));
        
        if (is_null($mobileFeedbackAdmin)) {

            $mobilefeedbackadmin = new MobileFeedBackAdmin();  
            $mobilefeedbackadmin->setAdminUser($this->getUser());
            $mobilefeedbackadmin->setMobileFeedback($params['mobileFeedback']);
            $this->em->persist($mobilefeedbackadmin);
            $this->em->flush();
        } 


    }
}
