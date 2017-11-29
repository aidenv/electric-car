<?php

namespace Yilinker\Bundle\CoreBundle\Services\Store;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Endroid\QrCode\QrCode;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Services\User\accountManager;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Utility\EntityService;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;

class StoreService extends EntityService
{
    protected $em;
    protected $tbStore;
    protected $productSearchService;
    protected $accountManager;
    protected $qrCodeGenerator;
    protected $container;

    protected $throwError = true;

    public function __construct(EntityManager $em, $productSearchService, $accountManager, $qrCodeGenerator)
    {
        $this->em = $em;
        $this->tbStore = $this->em->getRepository('YilinkerCoreBundle:Store');
        $this->productSearchService = $productSearchService;
        $this->accountManager = $accountManager;
        $this->qrCodeGenerator = $qrCodeGenerator;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Create a store for a user
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User
     * @return Yilinker\Bundle\CoreBundle\Entity\Store
     */
    public function createStore(User $user, $storeType = Store::STORE_TYPE_MERCHANT)
    {
        $store = null;
        if($user->getUserType() === User::USER_TYPE_SELLER){
            $store = new Store();

            $store->setUser($user);
            $store->setAccreditationLevel(null);
            $store->setStoreType($storeType);
            
            $this->em->persist($store);
            $user->setStore($store);
            $this->em->flush();
        }
        
        return $store;
    }

    /**
     * @return Yilinker\Bundle\CoreBundle\Entity\Store
     */
    public function findBySlug($slug)
    {
        $store = $this->tbStore->getActiveStore($slug);

        if (!$store && $this->throwError) {
            throw new NotFoundHttpException('Store with slug '.$slug.' does not exist');
        }

        return $store;
    }

    /**
     * @return used for product list filtering
     */
    public function filterMetaData($store)
    {
        $seller = $store->getUser();
        $productSearch = $this->productSearchService->searchProductsWithElastic(
            null,
            null,
            null,
            null,
            $seller->getId(),
            null,
            null,
            null,
            null,
            null,
            1,
            1,
            true
        );

        return $productSearch['aggregations'];
    }

    public function generateStoreNumber(Store $store)
    {
        $user = $store->getUser();

        if($store->getStoreType() == Store::STORE_TYPE_RESELLER){
            $prefix = "A";
        }
        else{
            $prefix = "M";
        }

        $storeNumber = $prefix.$store->getStoreId().date("Ymd").$user->getUserId();

        $storeRepository = $this->em->getRepository("YilinkerCoreBundle:Store");
        $duplicateStore = $storeRepository->findOneBy(array("storeNumber" => $storeNumber));

        while($duplicateStore){
            $storeNumber = $this->generateStoreNumber($store);
        }

        return $storeNumber;
    }

    /**
     * @return $user    the currently logged in user
     */
    public function getUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();

        $user = !is_null($token)? $token->getUser() : null;

        return $user;
    }

    /**
     * @return $store   the store object of the currently logged in user; 
     *                  service object will also be attached
     */
    public function getStore()
    {
        $user = $this->getUser();
        $store = null;
        if ($user && $store = $user->getStore()) {
            $store->service($this);
        }

        return $store;
    }

    public function getTotalEarning($filter = array(), $withTentative = true)
    {
        if (!$this->entity) {
            return 0;
        }

        $filter['status'] = $withTentative ? array(Earning::COMPLETE, Earning::TENTATIVE) : array(Earning::COMPLETE);
        $tbEarning = $this->em->getRepository('YilinkerCoreBundle:Earning');
        $totalEarning = $tbEarning->getStoreTotal($this->entity, $filter);

        return $totalEarning ? $totalEarning: 0;
    }

    public function getAvailableBalance($filter = array())
    {
        if (!$this->entity) {
            return 0;
        }

        $filter['status'] = array(Earning::COMPLETE, Earning::WITHDRAW);
        $tbEarning = $this->em->getRepository('YilinkerCoreBundle:Earning');
        $balance = $tbEarning->getStoreTotal($this->entity, $filter);
        $balance = $balance ? $balance: 0;
        $inprocess = $this->getInProcessWithdrawal($filter);

        return $balance - $inprocess;
    }

    public function getTentativeReceivable($filter = array())
    {
        if (!$this->entity) {
            return 0;
        }

        $filter['status'] = Earning::TENTATIVE;
        $tbEarning = $this->em->getRepository('YilinkerCoreBundle:Earning');
        $receivable = $tbEarning->getStoreTotal($this->entity, $filter);

        return $receivable ? $receivable: 0;
    }

    public function getTotalWithdrawn($filter = array())
    {
        if (!$this->entity) {
            return 0;
        }

        $filter['status'] = Earning::WITHDRAW;
        $tbEarning = $this->em->getRepository('YilinkerCoreBundle:Earning');
        $receivable = $tbEarning->getStoreTotal($this->entity, $filter);

        return $receivable ? abs($receivable): 0;
    }

    public function getDailyEarning($filter = array())
    {
        if (!$this->entity) {
            return 0;
        }

        $tbEarning = $this->em->getRepository('YilinkerCoreBundle:Earning');
        $dailyEarnings = $tbEarning->getDailyEarning($this->entity, $filter);

        return $dailyEarnings;
    }

    public function getInProcessWithdrawal($filter = array())
    {
        if (!$this->entity) {
            return 0;
        }

        $tbPayout = $this->em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $filter['status'] = PayoutRequest::PAYOUT_STATUS_PENDING;
        $inprocess = $tbPayout->getStoreTotal($this->entity, $filter);

        return $inprocess ? $inprocess: 0;
    }

    public function getEarningGroups($filter = array(), $number_format = true)
    {
        $tbEarningType = $this->em->getRepository('YilinkerCoreBundle:EarningType');
        $earningTypes = $tbEarningType->findAll();
        $earningGroups = array();

        foreach ($earningTypes as $earningType) {
            $earningTypeId = $earningType->getEarningTypeId();
            $filter['type'] = $earningTypeId;
            $totalEarning = $this->getTotalEarning($filter);

            $earningGroups[] = array(
                'name'          => $earningType->__toString(),
                'earningTypeId' => $earningTypeId,
                'totalAmount'   => $number_format ? number_format($totalEarning, 2): $totalEarning,
                'currencyCode'  => 'PHP'
            );
        }

        return $earningGroups;
    }

    public function bindPayoutRequest($payoutRequest, $store)
    {
        $user = $store->getUser();
        $bankAccount = $user->getDefaultBank();

        $payoutRequest->setRequestBy($user);
        $payoutRequest->setBank($bankAccount->getBank());
        $payoutRequest->setBankAccountTitle($bankAccount->getAccountTitle());
        $payoutRequest->setBankAccountName($bankAccount->getAccountName());
        $payoutRequest->setBankAccountNumber($bankAccount->getAccountNumber());
        $payoutRequest->setRequestSellerType($store->getStoreType());

        return $payoutRequest;   
    }

    /**
     * Get Store level with min and max store earning
     *
     * @param $storeLevelId
     * @param bool $currentOnly
     * @return array
     */
    public function getStoreLevel ($storeLevelId = null, $currentOnly = false)
    {
        $storeLevelEntities = $this->em->getRepository('YilinkerCoreBundle:StoreLevel')->getStoreLevelOrderBy();
        $storeLevelData = array ();
        $singleStoreLevel = array();

        if (sizeof($storeLevelEntities) > 0 ) {

            foreach ($storeLevelEntities as $count => $storeLevelEntity) {
                $min = 0;
                $max = $storeLevelEntity->getStoreEarning();
                $isCurrent = $storeLevelId == $storeLevelEntity->getStoreLevelId();

                if ($count !== 0 && isset($storeLevelEntities[$count - 1])) {
                    $min = $storeLevelEntities[$count - 1]->getStoreEarning() + 1;
                }

                $storeEarningRange = array (
                    'min' => $min,
                    'max' => $max
                );

                $storeLevelData[] = array (
                    'storeLevelId' => $storeLevelEntity->getStoreLevelId(),
                    'name'         => $storeLevelEntity->getName(),
                    'storeSpace'   => $storeLevelEntity->getStoreSpace(),
                    'isCurrent'    => $isCurrent,
                    'storeEarning' => $storeEarningRange,
                );

                if ($currentOnly && $isCurrent) {
                    $singleStoreLevel = array (
                        'storeLevelId' => $storeLevelEntity->getStoreLevelId(),
                        'name'         => $storeLevelEntity->getName(),
                        'storeSpace'   => $storeLevelEntity->getStoreSpace(),
                        'isCurrent'    => $isCurrent,
                        'storeEarning' => $storeEarningRange,
                    );
                }

            }

        }

        return $currentOnly ? $singleStoreLevel : $storeLevelData;
    }

    public function getNumberOfAvailableUploads (User $user)
    {
        $userUploadCount = $this->em->getRepository('YilinkerCoreBundle:User')
                                    ->getUserUploadCount($user, null, null, array(Product::ACTIVE, Product::FOR_REVIEW));
        $storeSpace = $user->getStore()->getStoreLevel()->getStoreSpace();

        return $storeSpace - $userUploadCount;
    }

    /**
     * Increment store view
     *
     * @param Store $store
     * @return boolean
     */
    public function incrementStoreview(Store $store)
    {
        $storeviews = $store->getStoreviews();
        $storeviews++;
        $storeviews = $store->setStoreviews($storeviews);

        $this->em->persist($storeviews);
        $this->em->flush();        
    }

}
