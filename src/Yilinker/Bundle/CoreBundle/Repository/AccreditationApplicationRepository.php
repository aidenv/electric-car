<?php

namespace Yilinker\Bundle\CoreBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\User;

class AccreditationApplicationRepository extends EntityRepository
{

    const LIMIT = 30;

    /**
     * Get Accreditation Application
     *
     * @param null $searchKeyword
     * @param null $userAccreditationTypeId
     * @param null $sellerType
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getAccreditationApplication (
        $searchKeyword = null,
        $userAccreditationTypeId = null,
        $sellerType = null,
        $offset = 0,
        $limit = self::LIMIT,
    	$resourceId = USER::RESOURCE_ALL_ID
    )
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                'User.userId',
                'AccApp.accreditationApplicationId',
                'AccLevel.accreditationLevelId AS accreditationLevelId',
                'AccApp.sellerType AS sellerType',
        		'AccApp.resourceId AS resourceId',
                "CONCAT(User.firstName, ' ', User.lastName) AS fullName",
                'Store.storeName AS storeName',
                'User.email',
                'User.contactNumber',
                'User as user',
            ))
            ->from('YilinkerCoreBundle:User', 'User')
            ->leftJoin('YilinkerCoreBundle:AccreditationApplication', 'AccApp', 'WITH', 'AccApp.user = User.userId')
            ->leftJoin('YilinkerCoreBundle:AccreditationLevel', 'AccLevel', 'WITH', 'AccLevel.accreditationLevelId = AccApp.accreditationLevel')
            ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId')
            ->where('User.userType = :userType')
            ->setParameter('userType', User::USER_TYPE_SELLER);

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("Store.storeName LIKE :searchKeyword OR CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($userAccreditationTypeId !== null) {

            if ( (int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_ACCREDITED) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NOT NULL")
                             ->andWhere("AccLevel.accreditationLevelId IS NOT NULL");
            }
            else if ((int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_WAITING) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NOT NULL")
                             ->andWhere("AccLevel.accreditationLevelId IS NULL");
            }
            else if ((int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_UNACCREDITED) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NULL");
            }

        }

        if ( (int) $resourceId !== USER::RESOURCE_ALL_ID) {
        	$queryBuilder ->andwhere('AccApp.resourceId = :resourceId')
        	->setParameter('resourceId', $resourceId);
        }
        
        if (!is_null($sellerType)) {
            $queryBuilder->andWhere('AccApp.sellerType = :sellerType or AccApp.sellerType IS NULL')
                         ->setParameter('sellerType', $sellerType);
        }

        $count = $this->getAccreditationCount($searchKeyword, $userAccreditationTypeId, $sellerType);

        $qbResult = $queryBuilder->setFirstResult($offset * $limit)
                                 ->setMaxResults($limit)
                                 ->getQuery();

        $result = $qbResult->getResult();

        if ($result) {
        	
        	$resourceIds = array(
        			0 => array(
        					'name' => 'From Buyer Page',
        					'id' => User::RESOURCE_BUYER_ID,
        			),
        			1 => array(
        					'name' => 'From Affiliate Page',
        					'id' => User::RESOURCE_AFFILIATE_ID,
        			),
        	);

            foreach ($result as &$accApplication) {
                $sellerType = $accApplication['sellerType'];

                if ($sellerType !== null && (int) $sellerType === AccreditationApplication::SELLER_TYPE_MERCHANT) {
                    $accApplication['sellerType'] = 'Seller';
                }
                else if ($sellerType !== null && (int) $sellerType === AccreditationApplication::SELLER_TYPE_RESELLER) {
                    $accApplication['sellerType'] = 'Affiliate';
                }

                if ($accApplication['accreditationApplicationId'] !== null && $accApplication['accreditationLevelId'] !== null) {
                    $accApplication['sellerStatus'] = 'Accredited';
                }
                else if ($accApplication['accreditationApplicationId'] !== null && $accApplication['accreditationLevelId'] === null) {
                    $accApplication['sellerStatus'] = 'Waiting for accreditation';
                }
                else if ($accApplication['accreditationApplicationId'] === null && $accApplication['accreditationLevelId'] === null) {
                    $accApplication['sellerStatus'] = 'Unaccredited';
                }
                
                try {
                    $accApplication['resource'] = $resourceIds[ $accApplication['resourceId'] ]['name'];
                } catch (\Exception $e) {
                    $accApplication['resource'] = 'N/A';
                }
            }

        }

        $arrayResult = compact (
            'result',
            'count'
        );

        return $arrayResult;
    }

    /**
     * Get accreditationCount
     *
     * @param null $searchKeyword
     * @param null $userAccreditationTypeId
     * @return mixed
     */
    public function getAccreditationCount (
        $searchKeyword = null,
        $userAccreditationTypeId = null,
        $sellerType = null
    )
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('COUNT(DISTINCT User.userId) AS accreditationCount')
                     ->from('YilinkerCoreBundle:User', 'User')
                     ->leftJoin('YilinkerCoreBundle:AccreditationApplication', 'AccApp', 'WITH', 'AccApp.user = User.userId')
                     ->leftJoin('YilinkerCoreBundle:AccreditationLevel', 'AccLevel', 'WITH', 'AccLevel.accreditationLevelId = AccApp.accreditationLevel')
                     ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId')
                     ->where('User.userType = :userType')
                     ->setParameter('userType', User::USER_TYPE_SELLER);

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("Store.storeName LIKE :searchKeyword OR CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($userAccreditationTypeId !== null) {

            if ( (int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_ACCREDITED) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NOT NULL")
                             ->andWhere("AccLevel.accreditationLevelId IS NOT NULL");
            }
            else if ((int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_WAITING) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NOT NULL")
                             ->andWhere("AccLevel.accreditationLevelId IS NULL");
            }
            else if ((int) $userAccreditationTypeId === AccreditationApplication::USER_APPLICATION_TYPE_UNACCREDITED) {
                $queryBuilder->andWhere("AccApp.accreditationApplicationId IS NULL");
            }

        }

        if (!is_null($sellerType)) {
            $queryBuilder->andWhere('AccApp.sellerType = :sellerType or AccApp.sellerType IS NULL')
                         ->setParameter('sellerType', $sellerType);
        }

        

        return $queryBuilder->getQuery()
                            ->getSingleScalarResult();
    }

}
