<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class UserPointApiController extends Controller
{
    const POINT_HISTORY_PER_PAGE = 15;

    /**
     * Retrieves the user point
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Add Point Module",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=true},
     *     }
     * )
     */
    public function getUserPointAction()
    {
        $earningGroup = $this->get('yilinker_core.service.earning.group');
        $totalEarnings = $earningGroup->getUserPoint($this->getUser());

        $response = array(
            'isSuccessful' => true,
            'message' => "User points retrieved",
            'data' => number_format($totalEarnings,2),
        );

        return new JsonResponse($response);
    }

    
    /**
     * Retrieves user point history
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Add Point Module",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=true},
     *         {"name"="limit", "dataType"="string", "required"=false},
     *         {"name"="page", "dataType"="string", "required"=false},
     *     }
     * )
     */
    public function getUserPointHistoryAction(Request $request)
    {
        $userId = $this->getUser()->getUserId();
        $usertype = $this->getUser()->getUserType();

        $params['limit'] = $request->get('limit',10);
        $params['offset'] = ($request->get('page',1) - 1) * $params['limit']; 


        if ($usertype == User::USER_TYPE_BUYER) {
        
            $up = $this->get('doctrine')->getRepository("YilinkerCoreBundle:UserPoint");
            $up->filterBy(array('user' => $userId));
            $up->setMaxResults($params['limit'])
                ->setFirstResult($params['offset']);


            $historyPoint = $this->hydrate($up->getQuery()->getResult());
            $m = 'point';

        } else if ($usertype == User::USER_TYPE_SELLER) {

            $historyPoint = $this->getEarningGroups($params);
            $m = 'earning';
        }


        $response = array(
            'isSuccessful' => true,
            'message' => "User $m history available",
            'data' => array(array(
                'type'  => $m == 'point' ? 'buyer' : 'seller',
                'points' => $historyPoint,
            )),
        );

        return new JsonResponse($response);
    }


    protected function getEarningGroups($params)
    {
        $earningGroup = $this->get('yilinker_core.service.earning.group');
        return $earningGroup->getUserEarningsByGroup($this->getUser(),null,$params['limit'],$params['offset'], array(Earning::INVALID));
    }


    protected function hydrate($history)
    {
        $response = array();
        $data = array();
        foreach($history as $h) {
            $data['amount'] = number_format($h->getPoints(),2);
            $data['description'] = $h->getDescription();
            $data['date'] = $h->getDateAdded()->format('m/d/Y H:i:s');
            
            array_push($response, $data);
        }

        return $response;
    }



}
