<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Carbon\Carbon;
use Gaufrette\Adapter\AwsS3;

class WidgetController extends Controller
{
    public function notificationsAction()
    {
        $notifications = $this->getUser()->getRecentNotifications(1, 10);
        $socket = array(
            'protocol' => $this->getParameter('protocol'),
            'host'  => $this->getParameter('node_host'),
            'port'  => $this->getParameter('node_port'),   
            's'     => $this->getUser()->hashkey()
        );
        $data = compact('notifications', 'socket');

        return $this->render(
            'YilinkerCoreBundle:Widget:notifications.html.twig', 
            $data
        );
    }

    public function manufacturersAction(Request $request)
    {
        $data = array(
            'success' => false,
            'results' => array()
        );

        $q = $request->get('q');
        $em = $this->getDoctrine()->getEntityManager();
        $tbManufacturer = $em->getRepository('YilinkerCoreBundle:Manufacturer');
        $manufacturers = $tbManufacturer->search($q);
        if ($manufacturers) {
            $data['success'] = true;
        }

        foreach ($manufacturers as $manufacturer) {
            $data['results'][] = array(
                'name'  => $manufacturer->getName(),
                'value' => $manufacturer->getManufacturerId()
            );
        }
        
        return new JsonResponse($data);
    }

    public function productCategoriesAction(Request $request)
    {
        $data = array(
            'success' => false,
            'results' => array()
        );
        $q = $request->get('q');
        $em = $this->getDoctrine()->getEntityManager();
        $tbProductCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $categories = $tbProductCategory->getCategoriesByKeyword($q, 10, false, true);
        if ($categories) {
            $data['success'] = true;
        }

        foreach ($categories as $category) {
            $data['results'][] = array(
                'name'  => $category->getName(),
                'value' => $category->getProductCategoryId()
            );
        }

        return new JsonResponse($data);
    }

    public function brandsAction(Request $request)
    {
        $data = array(
            'success' => false,
            'results' => array()
        );

        $q = $request->get('q');
        $em = $this->getDoctrine()->getEntityManager();
        $tbBrand = $em->getRepository('YilinkerCoreBundle:Brand');
        $brands = $tbBrand->getBrandByName($q);
        if ($brands) {
            $data['success'] = true;
        }

        foreach ($brands as $brand) {
            $data['results'][] = array(
                'name'  => $brand->getName(),
                'value' => $brand->getBrandId()
            );
        }

        return new JsonResponse($data);
    }

    public function uploadTempAction(Request $request)
    {
        $directory = $request->get('directory', '');

        $dir = $this->get('kernel')->getRootDir().'/../../web/'.$directory;
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        
        foreach ($request->files as $uploadedFile) {           
            $name = rand(1, 1000).rand(1, 1000).rand(1, 1000).'_'.strtotime('now').'_'.$uploadedFile->getClientOriginalName();
            $file = $uploadedFile->move($dir, $name);
        }
        
        return new JsonResponse(compact('name'));
    }

    public function uploadImageAction(Request $request)
    {
        $callBack = $request->get('CKEditorFuncNum');
        $imageLocation = array();
        foreach ($request->files as $uploadedFile) {
            /**
             * File Upload
             */
            $folderName = $this->get('kernel')->getRootDir().'/../../web/assets/images/uploads/misc';
            $fileName = rand(1, 1000) . '_' . strtotime(Carbon::now()).'.'.$uploadedFile->guessExtension();
            $file = $uploadedFile->move($folderName, $fileName);
        }
        $response = array(
            'data' => $this->getParameter('frontend_hostname').'/assets/images/uploads/misc/'.$fileName,
            'message' => ''
        );

        echo '<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(' . $callBack . ', "' . $response['data']. '","' . $response['message'] . '");</script></body></html>';;
    }

    public function productLanguagesAction(Request $request)
    {
        $product = $request->get('product');
        $em = $this->getDoctrine()->getEntityManager();
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $languages = $tbProduct->getLanguages($product, true);
        $data = compact('product', 'languages');

        return $this->render('YilinkerCoreBundle:Widget:product_languages.html.twig', $data);
    }

    /**
     * dropdown for countries
     */
    public function countriesAction(Request $request)
    {
        $selectedCountry = $request->get('country');
        $em = $this->getDoctrine()->getEntityManager();
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $countries = $tbCountry
            ->filterBy()
            ->setMaxResults(10)
            ->getResult()
        ;
        $data = compact('countries', 'selectedCountry');

        return $this->render('YilinkerCoreBundle:Widget:countries.html.twig', $data);
    }

    public function languagesAction(Request $request)
    {
        $selectedLanguage = $request->get('language');
        $em = $this->getDoctrine()->getEntityManager();
        $tbLanguage = $em->getRepository('YilinkerCoreBundle:Language');
        $languages = $tbLanguage
            ->filterBy()
            ->setMaxResults(10)
            ->getResult()
        ;
        $data = compact('languages', 'selectedLanguage');

        return $this->render('YilinkerCoreBundle:Widget:languages.html.twig', $data);
    }

    public function productCountriesAction(Request $request)
    {
        $product = $request->get('product');
        $status = $request->get('status');

        $em = $this->getDoctrine()->getEntityManager();
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');

        if ($status) {
            $countries = $tbProduct->getCountriesByStatus($product, $status);
        }
        else {
            $countries = $tbProduct->getCountries($product, true);
        }

        $data = compact('product', 'countries');

        return $this->render('YilinkerCoreBundle:Widget:product_countries.html.twig', $data);
    }

    public function productCategoryAction(Request $request)
    {
        $product = $request->get('product');
        $em = $this->getDoctrine()->getEntityManager();
        $tbProductCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        if ($product->getProductCategory()) {
            $categories = $tbProductCategory->getParentCategory(
                $product->getProductCategory()->getProductCategoryId()
            );
        }
        else {
            $categories = array();
        }
        $data = compact('categories');

        return $this->render('YilinkerCoreBundle:Widget:product_category.html.twig', $data);
    }
}
