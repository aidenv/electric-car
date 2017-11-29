<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;

class OneTimePasswordRepository extends EntityRepository
{
    public function getOneTimePasswordEntryCount(
        $user = null,
        $country = null,
        $contactNumber = null,
        $tokenType = OneTimePasswordService::OTP_TYPE_REGISTER,
        $isActive = null,
        $tokenExpiration = null
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("count(otp)")
                     ->from("YilinkerCoreBundle:OneTimePassword", "otp")
                     ->where("otp.country = :country")
                     ->andWhere("otp.contactNumber = :contactNumber")
                     ->andWhere("otp.tokenType = :tokenType")
                     ->setParameter(":country", $country)
                     ->setParameter(":contactNumber", $contactNumber)
                     ->setParameter(":tokenType", $tokenType);

        if(is_null($user)){
            $queryBuilder->andWhere("otp.user IS NULL");
        }
        else{
            $queryBuilder->andWhere("otp.user = :user")->setParameter(":user", $user);
        }

        if(!is_null($isActive)){
            $queryBuilder->andWhere("otp.isActive = :isActive")->setParameter(":isActive", $isActive);
        }

        if(!is_null($tokenExpiration)){
            $queryBuilder->andWhere("otp.tokenExpiration >= :tokenExpiration")->setParameter(":tokenExpiration", $tokenExpiration);
        }

        try{
            $result = $queryBuilder->getQuery()->getSingleScalarResult();
        }
        catch(\Exception $e){
            $result = 0;
        }

        return (int)$result;
    }

    /** 
     * onetimepassword list
     */
    public function getOnetimePasswordList($params=array())
    {
        $this->qb()
            ->orderBy('this.dateLastModified', 'DESC')
            ->addOrderBy('this.dateAdded', 'DESC');
            
        $qbResult = $this->qb()->paginate($params['page']);

        return $qbResult;
    }
}
