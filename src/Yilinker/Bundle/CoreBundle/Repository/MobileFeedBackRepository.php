<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class MobileFeedBackRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class MobileFeedBackRepository extends EntityRepository 
{

    /**
     * criteria for mobilefeedback
     */
    public function listCriteria($params=array())
    {
        $this->qb()
            ->select(array('this','mfa'))
            ->leftJoin('this.mobileFeedbackAdmins', 'mfa');

        $this->page($params['page']);

        $this->orderBy('mfa.mobileFeedbackAdminId', 'ASC')
            ->addOrderBy('this.dateAdded','DESC');

        return $this;
    }


    /** 
     *  List of MobileFeedBack
     */
    public function getList($params=array())
    {
        $this->listCriteria($params);
        $result = $this->getResult();

        $this->unBindOffset();
        $count = $this->getCount();
        $maxresult = $this->getQB()->getMaxResults();

        return compact('result','count', 'maxresult');
    } 
    
}