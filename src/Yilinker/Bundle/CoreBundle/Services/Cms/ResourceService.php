<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;

class ResourceService
{
    /**
     * Kernel
     *
     * @var string
     */
    private $kernel;

    /**
     * Container
     *
     * @var string
     */
    private $container;

    /**
     * @param $kernel
     * @param $container
     */
    public function __construct($kernel, $container)
    {
        $this->kernel = $kernel;
        $this->container = $container;
    }

    /**
     * Fetch the xml file of the target content
     *
     * @param string $page
     * @param string $version
     * @param string $platform
     * @return \SimpleXMLElement|null
     */
    public function fetchXML($page = "home", $version = "v1", $platform = "web")
    {
        $xmlPath = $this->__getXmlFile($page, $version, $platform);

        if (!is_null($xmlPath)) {
            return simplexml_load_file($xmlPath);
        }
        else {
            return null;
        }
    }

    /**
     * Save to xml
     *
     * @param $xml
     * @param string $page
     * @param string $version
     * @param string $platform
     * @return bool|null
     */
    public function saveXml($xml, $page = "home", $version = "v1", $platform = "web")
    {
        $result = null;
        $xmlPath = $this->__getXmlFile($page, $version, $platform);

        if (!is_null($xmlPath)) {
            $xml->asXML($xmlPath);
            $result = true;
        }

        return $result;
    }

    /**
     * Get XML File
     *
     * @param $page
     * @param $version
     * @param $platform
     * @return string|null
     */
    private function __getXmlFile ($page, $version, $platform)
    {
        try {
            $translationService = $this->container->get('yilinker_core.translatable.listener');
            $country = $translationService->getCountry();
            $env = $this->kernel->getEnvironment();
            $fileName = $platform . '.xml';
            $devXmlFile = "@YilinkerCoreBundle/Resources/content/{$country}/dev/$page/$version";
            $prodXmlFile = "@YilinkerCoreBundle/Resources/content/{$country}/prod/$page/$version";

            if (in_array($env, array('test', 'dev'))) {
                $xmlPath = $this->kernel->locateResource($devXmlFile . DIRECTORY_SEPARATOR . $fileName);
            }
            else {
                $xmlPath = $this->kernel->locateResource($prodXmlFile . DIRECTORY_SEPARATOR . $fileName);
            }
        }
        catch (\Exception $e) {
            $xmlPath = null;
        }

        return $xmlPath;
    }

}
