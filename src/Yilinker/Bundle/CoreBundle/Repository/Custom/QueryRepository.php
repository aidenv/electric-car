<?php

namespace Yilinker\Bundle\CoreBundle\Repository\Custom;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Gedmo\Translatable\TranslatableListener;

/**
 * Class QueryRepository
 * @package Yilinker\Bundle\CoreBundle\Custom
 */
class QueryRepository extends EntityRepository
{
    private $qb = null;
    protected $limit = 15;
    protected $hydrationMode = 1;
    // temporary value holder
    public $temp = array();
    public $searchByComparison = array();

    public function qb()
    {
        $this->qb = $this->createQueryBuilder('this');
        return $this;
    }

    public function setLimit($limit = 15)
    {
        $this->qb->setMaxResults($limit);

        return $this;
    }

    public function page($page)
    {
        $page = $page > 0 ? $page - 1 : 0;
        $limit = $this->qb->getMaxResults();
        if (!$limit) {
            $this->qb->setMaxResults($this->limit);
            $limit = $this->limit;
        }

        $this->qb->setFirstResult($page * $limit);
        return $this;
    }

    public function getTranslationQuery($locale = null, $innerJoin = false)
    {
        $query = $this
            ->qb
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            )
        ;

        if ($innerJoin) {
            $query->setHint(TranslatableListener::HINT_INNER_JOIN, true);
        }
        if (!is_null($locale)) {
            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        }

        return $query;
    }
    
    public function unBindOffset()
    {
        $this->qb->setFirstResult(0);

        return $this;
    }

    public function paginate($page ,$fetchJoinCollection = true)
    {
        $this->page($page);

        return new Paginator($this->qb->getQuery()->setHydrationMode($this->hydrationMode), $fetchJoinCollection);
    }

    public function getQB()
    {
        return $this->qb;
    }

    public function setHydrationMode($hydration)
    {
        $this->hydrationMode = $hydration;

        return $this;
    }

    public function getResult($hydrationMode = null)
    {
        if (is_null($hydrationMode)) {
            $hydrationMode = $this->hydrationMode;
        }
        $result = $this->getQB()->getQuery()->getResult($hydrationMode);

        return $result;
    }

    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->getResult($hydrationMode);

        return array_shift($result);
    }

    public function getOneOrNullResult()
    {
        return $this->getQB()
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getOneOrNullResult();
    }

    public function getCount()
    {
        $tempMaxResults = $this->qb->getMaxResults();
        $tempFirstResult = $this->qb->getFirstResult();

        $count = $this->select('count(distinct this)')
                      ->getQB()
                      ->setMaxResults(null)
                      ->setFirstResult(null)
                      ->getQuery()
                      ->getSingleScalarResult();
        $this->select('this');

        //return default pagination
        $this
            ->qb
            ->setMaxResults($tempMaxResults)
            ->setFirstResult($tempFirstResult)
        ;

        return $count;
    }

    public function getSum($columns)
    {
        $sum = $this->select("SUM($columns) as resultSum")
                    ->getQB()
                    ->getQuery()
                    ->getOneOrNullResult();
        $this->select('this');

        if (isset($sum['resultSum'])) {
            return (float) $sum['resultSum'];
        }

        return 0;
    }

    public function innerJoinWOCollision($join, $alias)
    {
        if (!$this->aliasExist($alias)) {
            $this->innerJoin($join, $alias);
        }

        return $this;
    }

    public function searchBy($criteria, $createQB = true)
    {
        if ($createQB) {
            $this->qb();
        }
        $this->temp['alienCriteria'] = array();

        $metadata = $this->getClassMetadata();
        foreach ($criteria as $crit => $value) {
            $breakdown = explode('.', $crit);
            $fieldName = array_shift($breakdown);
            $modifier = array_shift($breakdown);
            if ($metadata->hasField($fieldName) || $metadata->hasAssociation($fieldName)) {
                $this->createComparison($fieldName, $value, $modifier);
            }
            else {
                $this->temp['alienCriteria'][$fieldName] = $value;
            }
        }

        return $this;
    }

    public function createComparison($fieldName, $value, $modifier = null, $owner = 'this')
    {
        if (!$modifier && isset($this->searchByComparison[$fieldName])) {
            $modifier = $this->searchByComparison[$fieldName];
        }
        
        if ($modifier) {
            if ($modifier == 'from' && !is_null($value)) {
                $this
                    ->andWhere("$owner.$fieldName >= :".$modifier.$fieldName)
                    ->setParameter($modifier.$fieldName, $value)
                ;
            }
            elseif ($modifier == 'to' && !is_null($value)) {
                $this
                    ->andWhere("$owner.$fieldName <= :".$modifier.$fieldName)
                    ->setParameter($modifier.$fieldName, $value)
                ;
            }
            elseif ($modifier == 'exclude' && $value) {
                $this
                    ->andWhere("$owner.$fieldName NOT IN (:".$modifier.$fieldName.")")
                    ->setParameter($modifier.$fieldName, $value)
                ;
            }
            elseif ($modifier == 'ASC' || $modifier == 'DESC') {
                $this->orderBy("$owner.$fieldName", $modifier);
            }
        }
        else {
            if ($value) {
                $this
                    ->andWhere("$owner.$fieldName IN (:$fieldName)")
                    ->setParameter($fieldName, $value)
                ;
            }
        }

        return $this;
    }

    public function dumpQuery($exit = true)
    {
        $query = $this->getQB()->getQuery();
        dump($query->getSQL());
        $parameters = $query->getParameters()->toArray();
        foreach ($parameters as $parameter) {
            dump($parameter->getName());
            dump($parameter->getValue());
        }
        if ($exit) {
            exit;
        }
    }

    protected function aliasExist($alias)
    {
        $joinDqlParts = $this->getQB()->getDQLParts()['join'];
        $aliasAlreadyExists = false;

        foreach ($joinDqlParts as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    $aliasAlreadyExists = true;
                    break 2;
                }
            }
        }

        return $aliasAlreadyExists;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->qb, $name)) {
            call_user_func_array(array($this->qb, $name), $arguments);
        }
        else {
            return parent::__call($name, $arguments);
        }

        return $this;
    }
}