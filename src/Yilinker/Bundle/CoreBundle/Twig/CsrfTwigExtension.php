<?php
namespace Yilinker\Bundle\CoreBundle\Twig;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter;
use Twig_Extension;
use Twig_Function_Method;

/**
 * Default CSRF Twig Extension
 * @link http://www.corneliu.it/symfony-2-5-csrf-token-ajax-calls-twig-custom-function/
 */
class CsrfTwigExtension extends Twig_Extension
{
    /**
     * CSRF Provider
     * @var Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter
     */
    private $csrfProvider;

    /**
     * Default intention. 
     * @var string
     */
    private $intention;

    /**
     * Constructor
     * @param CsrfProvider $csrfProvider
     * @param string       $intention
     */
    public function __construct(CsrfTokenManagerAdapter $csrfProvider, $intention)
    {
        $this->csrfProvider = $csrfProvider;
        $this->intention = $intention;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
         return array(
             'default_csrf_token' => new Twig_Function_Method($this, 'getCsrfToken'),
         );
    }

    /**
     * Generates a CSRF depending on the intent
     * @return string
     */
    public function getCsrfToken()
    {
        return $this->csrfProvider->generateCsrfToken($this->intention);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'csrf_twig_extension';
    }

}
