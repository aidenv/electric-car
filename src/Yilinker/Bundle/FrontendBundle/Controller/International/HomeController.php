<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\International;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Country;

use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController as Controller;

class HomeController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $locationService = $this->get('yilinker_core.service.location.location');

        $domain = $request->query->has('no_redirect')
                  ? null
                  : $locationService->getDomainByIp($request->getClientIp());

        if ($domain && strlen(trim($domain))) {
            return $this->redirect($this->getParameter('protocol').'://'.$domain);
        }

        $countries = $em->getRepository('YilinkerCoreBundle:Country')->findByStatus(Country::ACTIVE_DOMAIN);
        $globalDomain = $this->getParameter('global_hostname');

        return $this->render('YilinkerFrontendBundle:Global:global_page.html.twig', compact('countries', 'globalDomain'));
    }
}
