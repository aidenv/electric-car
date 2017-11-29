<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AutoCompleteController extends Controller
{
    public function countriesAction(Request $request)
    {
        $q = $request->get('q', '');
        $lc = $request->get('lc');
        $useCode = (boolean)$request->get('useCode', true);
        $em = $this->getDoctrine()->getEntityManager();
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $countries = $tbCountry->filterBy(compact('q', 'lc'))->setMaxResults(10)->getResult();
        $data = array(
            'success' => true,
            'results' => array()
        );
        foreach ($countries as $country) {
            $data['results'][] = array(
                'name'  => $country->getName(),
                'value' => $useCode ? strtolower($country->getCode()): $country->getCountryId()
            );
        }

        return new JsonResponse($data);
    }

    public function languagesAction(Request $request)
    {
        $q = $request->get('q', '');
        $useCode = (boolean)$request->get('useCode', true);
        $em = $this->getDoctrine()->getEntityManager();
        $tbLanguage = $em->getRepository('YilinkerCoreBundle:Language');
        $languages = $tbLanguage->filterBy(compact('q'))->getResult();
        $data = array(
            'success' => true,
            'results' => array()
        );
        foreach ($languages as $language) {
            $data['results'][] = array(
                'name'  => $language->getName(),
                'value' => $useCode ? strtolower($language->getCode()): $language->getLanguageId()
            );
        }

        return new JsonResponse($data);
    }
}