<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class MerchantKernel extends Kernel
{
    
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Yilinker\Bundle\CoreBundle\YilinkerCoreBundle(),
            new Yilinker\Bundle\MerchantBundle\YilinkerMerchantBundle(),
            new React\Bundle\ServerSideRendererBundle\ReactServerSideRendererBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Endroid\Bundle\QrCodeBundle\EndroidQrCodeBundle(),
            new Exercise\HTMLPurifierBundle\ExerciseHTMLPurifierBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }
        else if (in_array($this->getEnvironment(), array('prod'))) {
            $bundles[] = new Snc\RedisBundle\SncRedisBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
