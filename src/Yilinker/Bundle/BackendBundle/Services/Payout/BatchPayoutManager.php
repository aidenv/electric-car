<?php

namespace Yilinker\Bundle\BackendBundle\Services\Payout;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchFile;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

/**
 * Class BatchPayoutManager
 * @package Yilinker\Bundle\BackendBundle\Services\Payout
 */
class BatchPayoutManager
{
    const FILE_DIRECTORY = 'images/uploads/payout_batch_file/';

    const DIGIT_CODE = 'BP';

    const PLATFORM = 'O';

    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct (EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->em = $entityManager;
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * Create BatchPayout
     *
     * @param array $payoutRequestEntities
     * @param AdminUser $adminUser
     * @param string $locale
     * @return null|array
     */
    public function createBatchPayoutByPayoutRequest (array $payoutRequestEntities = array(), AdminUser $adminUser, $locale = 'EN')
    {
        $response = array (
            'isSuccessful' => false,
            'data'         => array(),
            'message'      => ''
        );

        /**
         * Begin Transaction
         */
        $entityManager = $this->em;
        $entityManager->getConnection()->beginTransaction();
        try {
            $response['isSuccessful'] = true;
            $response['message'] = '';
            $payoutBatchHeadEntity = $this->createBatchPayoutHead ($adminUser, '', PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS, '', $locale);

            if ($payoutBatchHeadEntity instanceof PayoutBatchHead) {
                $response['data']['payoutBatchHead'] = $payoutBatchHeadEntity->toArray();

                if (sizeof($payoutRequestEntities) > 0) {

                    foreach ($payoutRequestEntities as $payoutRequestEntity) {

                        if ($payoutRequestEntity instanceof PayoutRequest) {
                            $payoutBatchDetail = $this->createBatchPayoutDetail($payoutBatchHeadEntity, $payoutRequestEntity);

                            if ($payoutBatchDetail instanceof PayoutBatchDetail) {
                                $response['data']['payoutBatchDetail'][] = $payoutBatchDetail->toArray();
                            }

                        }

                    }

                }

            }

            $entityManager->getConnection()->commit();
        }
        catch (\Exception $e) {
            $response = array (
                'isSuccessful' => false,
                'message'      => $e->getMessage()
            );
            $entityManager->getConnection()->rollback();
        }

        return $response;
    }

    /**
     * Create BatchPayoutHead
     *
     * @param AdminUser $adminUser
     * @param string $batchNumber
     * @param $payoutBatchStatusId
     * @param string $remarks
     * @param string $locale
     *
     * @return PayoutBatchHead
     */
    public function createBatchPayoutHead (
        AdminUser $adminUser,
        $batchNumber = '',
        $payoutBatchStatusId = PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS,
        $remarks = '',
        $locale = 'EN'
    ) {
        $batchNumber = $batchNumber == '' ? $this->generateBatchNumber(0, $locale) : $batchNumber;
        $batchPayoutHead = new PayoutBatchHead();
        $batchPayoutHead->setAdminUser($adminUser);
        $batchPayoutHead->setBatchNumber($batchNumber);
        $batchPayoutHead->setPayoutBatchStatus($payoutBatchStatusId);
        $batchPayoutHead->setRemarks($remarks);
        $batchPayoutHead->setDateAdded(Carbon::now());
        $batchPayoutHead->setDateLastModified(Carbon::now());
        $batchPayoutHead->setIsDelete(false);

        $this->em->persist($batchPayoutHead);
        $this->em->flush();

        return $batchPayoutHead;
    }

    /**
     * Create Batch payout detail
     *
     * @param PayoutBatchHead $payoutBatchHead
     * @param PayoutRequest $payoutRequest
     * @return PayoutBatchDetail
     */
    public function createBatchPayoutDetail (PayoutBatchHead $payoutBatchHead, PayoutRequest $payoutRequest)
    {
        $payoutBatchDetail = new PayoutBatchDetail();
        $payoutBatchDetail->setPayoutBatchHead($payoutBatchHead);
        $payoutBatchDetail->setPayoutRequest($payoutRequest);
        $payoutBatchDetail->setDateAdded(Carbon::now());
        $payoutBatchDetail->setDateLastModified(Carbon::now());
        $payoutBatchDetail->setIsDelete(false);

        $this->em->persist($payoutBatchDetail);
        $this->em->flush();

        if ($payoutBatchHead->getPayoutBatchStatus() == PayoutBatchHead::PAYOUT_BATCH_STATUS_DEPOSITED) {
            $payoutRequest->setPayoutRequestStatus(PayoutRequest::PAYOUT_STATUS_PAID);
            $this->earn($payoutRequest);
        }

        return $payoutBatchDetail;
    }

    /**
     * Create BatchPayoutFile
     *
     * @param PayoutBatchHead $payoutBatchHead
     * @param $file
     * @return PayoutBatchFile|null
     */
    public function addBatchPayoutFile (PayoutBatchHead $payoutBatchHead, $file)
    {
        $response = array (
            'isSuccessful' => false,
            'data'         => array(),
            'message'      => 'Image upload server error'
        );
        $payoutBatchFile = null;
        $fileName = $this->uploadFile($file, $payoutBatchHead->getPayoutBatchHeadId(), 'MB-Deposit-' . strtotime(Carbon::now()));

        if (!is_null($fileName)) {
            $payoutBatchFile = new PayoutBatchFile();
            $payoutBatchFile->setPayoutBatchHead($payoutBatchHead);
            $payoutBatchFile->setFileName($fileName);
            $payoutBatchFile->setDateAdded(Carbon::now());
            $payoutBatchFile->setDateLastModified(Carbon::now());
            $payoutBatchFile->setIsDelete(false);

            $this->em->persist($payoutBatchFile);
            $this->em->flush();

            $response = array (
                'isSuccessful' => true,
                'data'         => array (
                    'fileName'           => $fileName,
                    'payoutBatchFileId'  => $payoutBatchFile->getPayoutBatchFileId(),
                    'fullPath'           => $this->assetsHelper->getUrl(self::FILE_DIRECTORY . $payoutBatchHead->getPayoutBatchHeadId() . DIRECTORY_SEPARATOR . $fileName)
                ),
                'message'      => 'Successfully Uploaded!'
            );
        }

        return $response;
    }

    /**
     * Upload File
     * @param File $file
     * @param $folderName
     * @param $fileName
     * @return null|string
     */
    public function uploadFile (File $file, $folderName, $fileName)
    {
        $fileLocation = null;
        $fileWithExtension = $fileName . '.' . $file->getClientOriginalExtension();
        $fullPath = self::FILE_DIRECTORY . $folderName;

        if ($file instanceof UploadedFile &&
            $this->moveUploadedFile($file, 'assets/' . $fullPath, $fileWithExtension)) {
            $fileLocation = $fileWithExtension;
        }

        return $fileLocation;
    }

    /**
     * Move Uploaded File to Upload File Directory
     *
     * @param UploadedFile $file
     * @param $uploadDirectory
     * @param $fileWithExtension
     * @return string
     */
    public function moveUploadedFile(UploadedFile $file, $uploadDirectory, $fileWithExtension)
    {
        $isFileCreated = false;

        if (!file_exists($uploadDirectory)) {
            mkdir($uploadDirectory , 0777);
        }

        $file->move ($uploadDirectory, $fileWithExtension);

        if (file_exists($uploadDirectory)) {
            $isFileCreated = true;
        }

        return $isFileCreated;
    }

    /**
     * Generate Batch Number
     *
     * @param int $type
     * @param string $locale
     * @return string
     */
    public function generateBatchNumber ($type = 0, $locale = 'EN')
    {
        return self::DIGIT_CODE . '-' . self::PLATFORM . '-' . strtoupper($locale) . '-' . $type . '-' . strtotime(Carbon::now()) . rand(1, 999);
    }

    /**
     * Hard delete payout batch detail
     *
     * @param PayoutBatchDetail $payoutBatchDetail
     */
    public function deletePayoutBatchDetail (PayoutBatchDetail $payoutBatchDetail)
    {
        $payoutBatchDetail->setIsDelete(true);
        $this->em->flush();
    }

    /**
     * Get PayoutBatchStatus by payoutBatchStatusId
     *  If payoutBatchStatusId === null, It will return all PayoutBatchStatuses
     *
     * @param null $payoutBatchStatusId
     * @return array
     */
    public function getPayoutBatchStatus ($payoutBatchStatusId = null)
    {
        $payoutBatchStatuses = array (
            PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS => array (
                'id'   => PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS,
                'name' => 'In process'
            ),
            PayoutBatchHead::PAYOUT_BATCH_STATUS_DEPOSITED => array (
                'id'   => PayoutBatchHead::PAYOUT_BATCH_STATUS_DEPOSITED,
                'name' => 'Deposited'
            )
        );

        if (!is_null($payoutBatchStatusId) && isset($payoutBatchStatuses[$payoutBatchStatusId])) {
            $payoutBatchStatuses = array ($payoutBatchStatuses[$payoutBatchStatusId]);
        }

        return array_values($payoutBatchStatuses);
    }

    /**
     * Get Payout batch data
     *
     * @param null $searchBy
     * @param null $dateFrom
     * @param null $dateTo
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getPayoutBatchData (
        $searchBy = null,
        $dateFrom = null,
        $dateTo = null,
        $page = 1,
        $limit = 10
    ) {
        $payoutBatchHeadRepository = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchHead');
        $offset = $this->__getOffset($limit, $page);
        $payoutBatchHeadEntities = $payoutBatchHeadRepository->getPayoutBatchHead (
                                                                   $searchBy,
                                                                   new Carbon($dateFrom),
                                                                   new Carbon($dateTo),
                                                                   $offset,
                                                                   $limit
                                                               );

        $payoutBatchTotalCount = $payoutBatchHeadRepository->getPayoutBatchHeadCount (
                                                                 $searchBy,
                                                                 new Carbon($dateFrom),
                                                                 new Carbon($dateTo)
                                                             );
        $payoutBatchTotalAmount = $payoutBatchHeadRepository->getPayoutBatchTotalAmount (
                                                                  $searchBy,
                                                                  new Carbon($dateFrom),
                                                                  new Carbon($dateTo)
                                                              );

        return compact('payoutBatchHeadEntities', 'payoutBatchTotalCount', 'payoutBatchTotalAmount');
    }

    /**
     * Delete payoutBatchHead and detail
     *
     * @param PayoutBatchHead $payoutBatchHead
     * @return array
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function deletePayoutBatch (PayoutBatchHead $payoutBatchHead)
    {
        $response = array (
            'isSuccessful' => true,
            'message'      => 'Successfully Removed!'
        );
        $payoutBatchDetailEntities = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchDetail')
                                              ->findByPayoutBatchHead($payoutBatchHead);
        $payoutBatchFileEntities = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchFile')
                                            ->findByPayoutBatchHead($payoutBatchHead);

            /**
             * Begin Transaction
             */
            $entityManager = $this->em;
            $entityManager->getConnection()->beginTransaction();
            try {

                if (sizeof($payoutBatchDetailEntities) > 0) {

                    foreach ($payoutBatchDetailEntities as $payoutBatchDetailEntity) {

                        if ($payoutBatchDetailEntity instanceof PayoutBatchDetail) {
                            $this->deletePayoutBatchDetail($payoutBatchDetailEntity);
                        }

                    }

                }

                if (sizeof($payoutBatchFileEntities) > 0) {

                    foreach ($payoutBatchFileEntities as $payoutBatchFileEntity) {

                        if ($payoutBatchFileEntity instanceof PayoutBatchFile) {
                            $this->deletePayoutBatchFile($payoutBatchFileEntity);
                        }

                    }

                }

                $payoutBatchHead->setIsDelete(true);
                $this->em->flush();
                $entityManager->getConnection()->commit();
            }
            catch (\Exception $e) {
                $response = array (
                    'isSuccessful' => false,
                    'message'      => 'Server Error'
                );
                $entityManager->getConnection()->rollback();
            }

        return $response;
    }

    /**
     * Hard delete payoutBatchFile
     *
     * @param PayoutBatchFile $payoutBatchFile
     */
    public function deletePayoutBatchFile (PayoutBatchFile $payoutBatchFile)
    {
        $payoutBatchFile->setIsDelete(true);
        $this->em->flush();
    }

    /**
     * Create Bulk PayoutBatch
     *
     * @param array $payoutRequestEntities
     * @param AdminUser $adminUser
     * @param $payoutBatchStatus
     * @param $remarks
     * @param $files
     * @param string $locale
     * @return array
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function createBulkBatchPayout (array $payoutRequestEntities, AdminUser $adminUser, $payoutBatchStatus, $remarks, $files = array(), $locale = 'EN')
    {
        $response = array (
            'isSuccessful' => false,
            'data'         => array(),
            'message'      => ''
        );

        /**
         * Begin Transaction
         */
        $entityManager = $this->em;
        $entityManager->getConnection()->beginTransaction();
        try {
            $response['isSuccessful'] = true;
            $response['message'] = '';
            $payoutBatchHeadEntity = $this->createBatchPayoutHead($adminUser, '', $payoutBatchStatus, $remarks, $locale);

            if ($payoutBatchHeadEntity instanceof PayoutBatchHead) {
                $response['data']['payoutBatchHead'] = $payoutBatchHeadEntity->toArray();

                foreach ($payoutRequestEntities as $payoutRequestEntity) {

                    if ($payoutRequestEntity instanceof PayoutRequest) {
                        $payoutBatchDetail = $this->createBatchPayoutDetail($payoutBatchHeadEntity, $payoutRequestEntity);

                        if ($payoutBatchDetail instanceof PayoutBatchDetail) {
                            $response['data']['payoutBatchDetail'][] = $payoutBatchDetail->toArray();
                        }

                    }

                }

                if (sizeof($files) > 0) {

                    foreach ($files as $file) {

                        if ($file instanceof File) {
                            $this->addBatchPayoutFile ($payoutBatchHeadEntity, $file);
                        }

                    }
                }

            }

            $entityManager->getConnection()->commit();
        }
        catch (\Exception $e) {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Server Error'
            );
            $entityManager->getConnection()->rollback();
        }

        return $response;
    }

    /**
     * Get Batch payout data
     *
     * @param PayoutBatchHead $payoutBatchHead
     * @return array
     */
    public function getBatchPayoutData (PayoutBatchHead $payoutBatchHead)
    {
        $payoutBatchDetailArray = array();
        $payoutBatchFileArray = array();
        $payoutBatchDetailEntities = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchDetail')
                                              ->findBy(array(
                                                  'payoutBatchHead' => $payoutBatchHead,
                                                  'isDelete'        => false
                                              ));
        $payoutBatchFileEntities = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchFile')
                                            ->findBy(array(
                                                'payoutBatchHead' => $payoutBatchHead,
                                                'isDelete'        => false
                                            ));

        if (sizeof($payoutBatchDetailEntities) > 0) {

            foreach ($payoutBatchDetailEntities as $payoutBatchDetailEntity) {
                $payoutBatchDetailArray[] = $payoutBatchDetailEntity->toArray();
            }

        }

        if (sizeof($payoutBatchFileEntities) > 0) {

            foreach ($payoutBatchFileEntities as $payoutBatchFileEntity) {
                $payoutBatchFileArray[] = array (
                    'payoutBatchFileId' => $payoutBatchFileEntity->getPayoutBatchFileId(),
                    'fileName'          => $payoutBatchFileEntity->getFileName(),
                    'file'              => $this->assetsHelper->getUrl(self::FILE_DIRECTORY . $payoutBatchHead->getPayoutBatchHeadId() . DIRECTORY_SEPARATOR . $payoutBatchFileEntity->getFileName())
                );
            }

        }

        return array (
            'payoutBatchHead'   => $payoutBatchHead->toArray(),
            'payoutBatchDetail' => $payoutBatchDetailArray,
            'payoutBatchFile'   => $payoutBatchFileArray
        );
    }

    /**
     * Earn
     *
     * @param PayoutRequest $payoutRequest
     * @return Earning
     * @throws \Doctrine\ORM\ORMException
     */
    public function earn (PayoutRequest $payoutRequest)
    {
        $earningTypeReference = $this->em->getReference('YilinkerCoreBundle:EarningType', EarningType::WITHDRAW);

        $earning = new Earning();
        $earning->setEarningType($earningTypeReference);
        $earning->setUser($payoutRequest->getRequestBy());
        $earning->setAmount(-$payoutRequest->getRequestedAmount());
        $earning->setStatus(Earning::WITHDRAW);
        $earning->setDateLastModified(Carbon::now());
        $earning->setDateAdded(Carbon::now());

        $this->em->persist($earning);
        $this->em->flush();

        return $earning;
    }

    /**
     * Calculate offset
     *
     * @param int $limit
     * @param int $page
     * @return int
     */
    private function __getOffset ($limit = 10, $page = 0)
    {
        return (int) $page > 1 ? (int) $limit * ((int) $page-1) : 0;
    }

}
