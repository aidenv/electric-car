<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class YilinkerBaseController extends Controller
{
    protected $jsonResponse = array(
        'isSuccessful'  => false,
        'data'          => array(),
        'message'       => ''
    );

    protected $redirectURL = '/';

    public function jsonResponse()
    {
        return new JsonResponse($this->jsonResponse, $this->jsonResponse['isSuccessful'] ? 200 : 400 );
    }

    /**
     * Invalidate an authenticated user
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Symfony\Component\HttpFoundation\Response response
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticateUser(Request $request, $response)
    {
        // Logging user out.
        $this->get('security.token_storage')->setToken(null);

        // Invalidating the session.
        $session = $request->getSession();
        $session->invalidate();

        // Clearing the cookies.
        $cookieNames = array(
            $this->container->getParameter('session.name'),
            $this->container->getParameter('session.remember_me.name'),
        );
        foreach ($cookieNames as $cookieName) {
            $response->headers->clearCookie($cookieName);
        }

        return $response;
    }

    /**
     * Retrieve controllerdata from cache
     *
     * @param string $key
     * @param boolean $jsondecode
     * @param boolean $associative
     * @return mixed
     */
    public function getCacheValue($key, $jsondecode = false, $associative = true)
    {
        $redis = $this->container->has('snc_redis.default') ? $this->get('snc_redis.default') : null;        
        if($redis && $redis->get($key)){

            $result = $redis->get($key);
            if($jsondecode){
                $result = json_decode($result, $associative);
            }

            return $result;
        }
        
        return false;
    }

    /**
     * Set controller data into cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $expirationSec
     * @return boolean
     */
    public function setCacheValue($key, $value, $expirationSec = 3600)
    {
        $redis = $this->container->has('snc_redis.default') ? $this->get('snc_redis.default') : null;

        if($redis){
            if(is_array($value) || is_object($value)){
                $value = json_encode($value);
            }
            $redis->set($key, $value);
            $redis->expire($key, $expirationSec);

            return true;
        }

        return false;
    }

    public function getAppCountry()
    {
        $host = $this->get('request')->getHost();
        $em = $this->getDoctrine()->getManager();
        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');

        $country = $countryRepository->findOneByDomain($host);

        if ($country) {
            return $country;
        }

        return $countryRepository->findFirst();
    }

    public function getCountryByApi()
    {
        return $this->get('yilinker_core.service.location.location')->getCountryByApi();
    }

    public function getV3Path()
    {
        $path = array(
            'country_code' => 'ph',
            'language_code' => 'en'
        );

        $pathInfo = explode('/',$this->container->get('request')->getPathInfo());
        
        if (isset($pathInfo[3]) && $pathInfo[2] == 'v3') {
            $path['country_code'] = $pathInfo[3];
            $path['language_code'] = $pathInfo[4];
        }

        return $path;
    }

    public function throwNotFoundUnless($test, $message = 'Not Found')
    {
        if (!$test) {
            throw $this->createNotFoundException($message);
        }
    }

    public function isMobile(Request $request)
    {
        $useragent = $request->headers->get('User-Agent');
        
        return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
    }

    public function redirectBack() {
        $url = $this->redirectURL;
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $url = $_SERVER['HTTP_REFERER'];
        }

        return $this->redirect($url);
    }
}
