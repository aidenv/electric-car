<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class PointsController extends YilinkerBaseController
{
    /**
     * Render Profile My Points Markup
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function myPointsAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $pointQuery = $em->getRepository('YilinkerCoreBundle:UserPoint')
                         ->filterBy(array('user' => $this->getUser()));

        $totalPoints = $pointQuery->getSum('this.points');

        $pointHistory = $pointQuery->paginate($request->get('page', 1));

        return $this->render('YilinkerFrontendBundle:Profile:profile_my_points.html.twig', array(
            'pointHistory' => $pointHistory,
            'totalPoints' => $totalPoints,
        ));
    }
}
