<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\Common\Util\Debug;

class HomeController extends Controller
{
    const DASHBOARD_OVERVIEW_ITEM_COUNT = 5;

    public function renderMarkupsAction()
    {
        return $this->render('YilinkerBackendBundle:home:markups.html.twig');
    }

    public function renderAccessDeniedAction()
    {
        return $this->render('YilinkerBackendBundle:AccessDenied:accessDenied.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsProductAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:product.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsProductDetailsAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:product_details.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsBrandAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:brand.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsBrandDetailsAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:brand_details.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsSellerAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:seller.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsSellerDetailsAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:seller_details.html.twig');
    }

    /** Need to be revise, for CMS markup purpose only **/
    public function renderCmsBannerAction()
    {
        return $this->render('YilinkerBackendBundle:Cms:banner.html.twig');
    }

    /**
     * Render home page
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderHomepageAction()
    {
        $em = $this->getDoctrine()->getManager();
        $transactionService = $this->get('yilinker_core.service.transaction');
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $userRepository = $em->getRepository('YilinkerCoreBundle:User');
        $disputeRepository = $em->getRepository('YilinkerCoreBundle:Dispute');
      
      $transactions = $userOrderRepository->getTransactionOrder(
           null, null, null, null,
            null, null, 0, self::DASHBOARD_OVERVIEW_ITEM_COUNT
        ); 
 
        $disputes = $disputeRepository->getCase(
            null, null, null, null,
            null, null, null, 0, self::DASHBOARD_OVERVIEW_ITEM_COUNT
        );
      
        $orderProductStatuses = $transactionService->getOrderProductStatusesForRefund();
        $refunds = $userOrderRepository->qb()
                                       ->getBuyerRefundList()
                                       ->setLimit(self::DASHBOARD_OVERVIEW_ITEM_COUNT)
                                       ->getResult();
        
        $daysElapsed = \Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService::PAYOUT_DAYS_ELAPSED;
        $payouts = $userOrderRepository->getSellerPayoutList(
            null, null, null, $daysElapsed, 0, self::DASHBOARD_OVERVIEW_ITEM_COUNT
        );

        $productStatuses = array(
                                    Product::ACTIVE,
                                    Product::FOR_REVIEW,
                                    Product::DELETE,
                                    Product::REJECT,
                                );

        $productSearchService = $this->get('yilinker_core.service.search.product');
        $productSearch = $productSearchService->searchProductsWithElastic(
            null, null, null, null, null, null,
            null, null, null, null, null, null,
            true, true, null, null, null, $productStatuses,
            null, null, null, null, array(), null
        );

        $numberOfBuyers = $userRepository->getNumberOfUsers(User::USER_TYPE_BUYER, true);
        $numberOfMerchants = $userRepository->getNumberOfUsers(User::USER_TYPE_SELLER, true);
        $numberOfTransactions = $userOrderRepository->getTransactionOrderCount();

        return $this->render('YilinkerBackendBundle:home:home.html.twig', array(
            'transactions'              => $transactions,
            'disputes'                  => $disputes['cases'],
            'refunds'                   => $refunds,
            'payouts'                   => $payouts['sellers'],
            'numberOfActiveProducts'    => $productSearch['totalResultCount'],
            'numberOfBuyer'             => $numberOfBuyers,
            'numberOfMerchants'         => $numberOfMerchants,
            'numberOfTransactions'      => $numberOfTransactions,
        ));
    }

    /**
     * Render the sidebar
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderSideBarAction()
    {
        $em = $this->getDoctrine()->getManager();
        $disputeCount = $em->getRepository('YilinkerCoreBundle:Dispute')
                           ->getTotalNumberOfCases(null, null, DisputeStatusType::STATUS_TYPE_OPEN);

        $crmHostName = $this->getParameter("crm_hostname");
        return $this->render('YilinkerBackendBundle:base:sidebar.html.twig', array(
            'disputeCount' => $disputeCount,
            "crmHostName" => $crmHostName
        ));
    }

    /**
     * Render Header
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderHeaderAction()
    {
        $localeString = trim($this->getParameter('app.locales'), '|');
        $locales =  explode('|', $localeString);
        return $this->render('YilinkerBackendBundle:base:header.html.twig', array(
            'locales' => $locales,
        ));
    }
}
