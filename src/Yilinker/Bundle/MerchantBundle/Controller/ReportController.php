<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Repository\EarningRepository;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;

use \DatePeriod;
use \DateInterval;
use \DateTime;

/**
 * Class ReportController
 *
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class ReportController extends Controller
{
    /**
     * Render Dashboard Reports Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardReportsAction(Request $request)
    {
        $currentDate = date('Y-m-d');
        $minusTwoWeeks = date('Y-m-d', strtotime("$currentDate -2 weeks"));

        $keyword = (string) $request->get('keyword', '');
        $dateFrom = (string) $request->get('dateFrom', $minusTwoWeeks);
        $dateTo = (string) $request->get('dateTo', $currentDate);
        $dateFrom = trim($dateFrom) !== "" && DateTime::createFromFormat('Y-m-d', $dateFrom) ? $dateFrom : $minusTwoWeeks;
        $dateTo = trim($dateTo) !== "" && DateTime::createFromFormat('Y-m-d', $dateTo) ? $dateTo : $currentDate;
        $filterInvalid = $request->query->has('invalid');
        $filterSales = $request->query->has('sales');

        $queryCount = $request->query->count();
        if ($request->query->has('page')) {
            $queryCount --;
        }

        $filterData = !($queryCount > 0 && !$filterInvalid && !$filterSales);

        $em = $this->getDoctrine()->getManager();
        $orderProductRepository = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $earningRepository = $em->getRepository('YilinkerCoreBundle:Earning');

        $filters = EarningRepository::getFilterCriterias();

        $filterQuery = array(
            'seller'                    => $this->getUser(),
            'lastDateModified.dateFrom' => $dateFrom,
            'lastDateModified.dateTo'   => $dateTo,
            'orderProductStatus'        => array(),
        );

        $salesDataCount = $this->createDates($dateFrom, $dateTo);
        $invalidDataCount = $salesDataCount;
        $totalSales = 0;
        $totalCharges = 0;
        $totaActiveReceivable = 0;
        $totalEarning = 0;
        $totaTentativeEarning = 0;
        $totalCompleteEarning = 0;
        $orderProducts = array();

        if ($filterData) {
            $searchFilters = $this->validateFilters($request->query->all(), $filters);
            $searchFilters['isFlagged'] = false;
            
            $orderProductQuery = $orderProductRepository->qb()
                                                        ->whereQuery(array_merge($filterQuery, $searchFilters))
                                                        ->joinInOrder($searchFilters);

            if ($filterSales || $queryCount <= 0) {
                $filterQuery['orderProductStatus'] = array_merge(
                    $filterQuery['orderProductStatus'],
                    TransactionService::getOrderProductSalesStatuses()
                );

                $orderProductQuery->whereQuery(array('orderProductStatus' => TransactionService::getOrderProductSalesStatuses()));

                $orderProductIds = array();
                foreach ($orderProductQuery->getResult() as $orderProduct) {
                    $orderProductIds[] = $orderProduct->getOrderProductId();
                }

                $totalSold = $orderProductRepository->getTotalSoldWithIds($orderProductIds);
                foreach ($totalSold as $value) {
                    $salesDataCount[$value['lastDateModified']] = (int) $value['transactionCount'];
                }
                ksort($salesDataCount);
            }

            if ($filterInvalid) {
                $filterQuery['orderProductStatus'] = array_merge(
                    $filterQuery['orderProductStatus'],
                    TransactionService::getOrderProductReturnOrderStatuses()
                );

                $orderProductQuery->whereQuery(array('orderProductStatus' => TransactionService::getOrderProductReturnOrderStatuses()));

                $orderProductIds = array();
                foreach ($orderProductQuery->getResult() as $orderProduct) {
                    $orderProductIds[] = $orderProduct->getOrderProductId();
                }

                $totalInvalid = $orderProductRepository->getTotalSoldWithIds($orderProductIds);
                foreach ($totalInvalid as $value) {
                    $invalidDataCount[$value['lastDateModified']] = (int) $value['transactionCount'];
                }
                ksort($invalidDataCount);
            }

            $orderProductQuery->whereQuery(array('orderProductStatus' => $filterQuery['orderProductStatus']));


            if (($filterInvalid || $filterSales) || $queryCount <= 0) {
                $totalSales = $orderProductQuery->getSum('this.totalPrice');

                $filterEarning = array(
                    'daterange' => date('m/d/Y', strtotime($dateFrom)).' - '.date('m/d/Y', strtotime($dateTo)),
                    'q' => $request->get('keyword'),
                    'qCriteria' => $request->get('searchType')
                );

                $filterEarning['status'] = Earning::WITHDRAW;
                $withdrawnEarning = $earningRepository->ofStoreQB($this->getUser()->getStore(), $filterEarning)
                                                      ->getSum('this.amount');

                $filterEarning['status'] = Earning::COMPLETE;
                $completeEarning = $earningRepository->ofStoreQB($this->getUser()->getStore(), $filterEarning)
                                                     ->getSum('this.amount');

                if ($this->getUser()->getStore()->isAffiliate()) {
                    $filterEarning['status'] = Earning::TENTATIVE;
                    $totaTentativeEarning = $earningRepository->ofStoreQB($this->getUser()->getStore(), $filterEarning)
                                                              ->getSum('this.amount');
                }
                else {
                    $totalCharges = $orderProductQuery->getSum('this.additionalCost + this.yilinkerCharge + this.handlingFee');
                }

                $totalCompleteEarning = bcadd($completeEarning, $withdrawnEarning, 2);
            }

            $orderProducts = $orderProductQuery->paginate($request->get('page', 1));
        }

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_reports.html.twig', array(
            'orderProducts' => $orderProducts,
            'salesDataCount' => $salesDataCount,
            'invalidDataCount' => $invalidDataCount,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalSales' => $totalSales,
            'totalCompleteEarning' => $totalCompleteEarning,
            'sellerData' => array(
                'totalCharges' => $totalCharges,
                'totalNetReceivables' => bcsub($totalSales, $totalCharges, 2),
            ),
            'affiliateData' => array(
                'totalEarning' => $totalCompleteEarning + $totaTentativeEarning,
                'totaTentativeEarning' => $totaTentativeEarning,
            ),
            'filters' => $filters,
            'queryCount' => $queryCount
        ));
    }

    /**
     * TODO: Move to helper if needed.
     * Simply create date range within given range
     * @link   http://stackoverflow.com/a/33833432/4286267
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    private function createDates($dateFrom, $dateTo)
    {
        $dates = array();
        $period = new DatePeriod(new DateTime($dateFrom), new DateInterval('P1D'), new DateTime("$dateTo +1 day"));
        foreach ($period as $date) {
            $dates[$date->format("Y-m-d")] = 0;
        }

        return $dates;
    }

    private function validateFilters(array $data, array $filters)
    {
        $filterArray = array();

        if (isset($data['searchType']) && isset($filters[$data['searchType']])) {
            if ((int) $data['searchType'] === EarningRepository::FILTER_TRANSACTION_NUMBER) {
                $filterArray['invoiceNumber'] = $data['keyword'];
            }
            else if ((int) $data['searchType'] === EarningRepository::FILTER_PRODUCT_NAME) {
                $filterArray['productName'] = $data['keyword'];
            }
            else if ((int) $data['searchType'] === EarningRepository::FILTER_BUYER_NAME) {
                $filterArray['buyer'] = $data['keyword'];
            }
        }

        return $filterArray;
    }
}
