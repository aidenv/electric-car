<?php

namespace Yilinker\Bundle\BackendBundle\Tests\Controller;

use Yilinker\Bundle\BackendBundle\Tests\YilinkerBackendWebTestCase as WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PermissionControllerTest extends WebTestCase
{
    /**
     * @dataProvider roleDataProvider
     */
    public function testPermissionSuccessHomeAction($user, $accessiblePages, $deniedPages)
    {
        $client = $this->createAuthenticatedUser($user);

        foreach ($accessiblePages as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertEquals(
                Response::HTTP_OK,
                $client->getResponse()->getStatusCode()
            );
        }

        foreach ($deniedPages as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertEquals(
                Response::HTTP_FORBIDDEN,
                $client->getResponse()->getStatusCode()
            );
        }
    }

    public function roleDataProvider()
    {
        $allPages = array(
            '/',
            '/transactions',
            '/seller-payout',
            '/buyer-refund',
            '/promo/promo-management',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
            '/inhouse-products',
            '/user/buyer',
            '/user/seller',
            '/user/affiliate',
            '/product/listings',
            '/vouchers',
            '/request-payout-list',
            '/payout-batch-list',
            '/admin/register',
            '/resolution-center',
            '/buyer-refund/history'
        );

        $sellerSpecialistPages = array(
            '/',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
            '/user/seller',
            '/user/affiliate',
        );

        $productSpecialistPages = array(
            '/',
            '/inhouse-products',
            '/product/listings',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
        );

        $csrPages = array(
            '/',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
            '/transactions',
            '/user/buyer',
            '/user/seller',
            '/user/affiliate',
            '/resolution-center'
        );

        $marketingPages = array(
            '/',
            '/promo/promo-management',
            '/transactions',
            '/vouchers',
            '/cms/product-lists',
            '/cms/brand-list',
            '/push-notification/',
        );

        $accountingPages = array(
            '/',
            '/seller-payout',
            '/buyer-refund',
            '/request-payout-list',
            '/payout-batch-list',
            '/transactions',
            '/buyer-refund/history',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
        );

        $operationsAdminPages = array(
            '/',
            '/transactions',
            '/seller-payout',
            '/buyer-refund',
            '/promo/promo-management',
            '/accreditation/applications/Seller',
            '/accreditation/applications/Affiliate',
            '/inhouse-products',
            '/user/buyer',
            '/user/seller',
            '/user/affiliate',
            '/product/listings',
            '/vouchers',
            '/request-payout-list',
            '/payout-batch-list',
            '/resolution-center',
            '/buyer-refund/history',
        );

        return array(
            array('admin', $allPages, array()),
            array('seller_specialist', $sellerSpecialistPages, array_diff($allPages, $sellerSpecialistPages)),
            array('product_specialist', $productSpecialistPages, array_diff($allPages, $productSpecialistPages)),
            array('csr', $csrPages, array_diff($allPages, $csrPages)),
            array('marketing', $marketingPages, array_diff($allPages, $marketingPages)),
            array('accounting', $accountingPages, array_diff($allPages, $accountingPages)),
            array('operations_admin', $operationsAdminPages, array_diff($allPages, $operationsAdminPages))
        );
    }
}
