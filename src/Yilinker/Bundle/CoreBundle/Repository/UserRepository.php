<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Doctrine\ORM\Query\Expr\Join;
use DateTime;

/**
 * Class UserRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{

    const PAGE_LIMIT = 30;

    /**
     * Load users where in. index will be the user id
     *
     * @param $userIds
     * @return array
     */
    public function loadUsersIn($userIds)
    {
        $users = $this->_em
                      ->createQueryBuilder()
                      ->select("u")
                      ->from("YilinkerCoreBundle:User", "u", "u.userId")
                      ->where("u.userId IN (:userIds)")
                      ->setParameter(":userIds", $userIds)
                      ->getQuery()
                      ->getResult();

        return $users;
    }

    /**
     * Fetch user by email
     *
     * @param string $email
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($email)
    {
        $user = $this->createQueryBuilder('u')
                     ->where('u.email = :email')
                     ->setParameter('email', $email)
                     ->getQuery()
                     ->setMaxResults(1)
                     ->getOneOrNullResult();

        return $user;
    }

    /**
     * Fetch user by either contactNumber or email
     *
     * @param $request
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByContactOrEmail($request, $isSeller = false, $isAffiliate = false, $excludedUserId = null)
    {
        $queryBuilder = $this->createQueryBuilder('u')
                             ->where('u.email = :request')
                             ->orWhere('u.contactNumber = :request')
                             ->setParameter('request', $request);

        if($isSeller){

            $queryBuilder->andWhere("u.userType = :userType")
                         ->setParameter(":userType", User::USER_TYPE_SELLER);

            $queryBuilder->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
                         ->andWhere("s.storeType = :storeType")
                         ->setParameter(":storeType", $isAffiliate? Store::STORE_TYPE_RESELLER : Store::STORE_TYPE_MERCHANT);
        }
        else{
            $queryBuilder->andWhere("u.userType = :userType")
                         ->setParameter(":userType", User::USER_TYPE_BUYER);
        }

        if(!is_null($excludedUserId)){
            $queryBuilder->andWhere("u.userId != :userId")->setParameter(":userId", $excludedUserId);
        }

        return $queryBuilder->getQuery()
                            ->setMaxResults(1)
                            ->getOneOrNullResult();
    }

    /**
     *
     * @param $request
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByOneTimePassword($token, $isSeller = false, $isAffiliate = false)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        if($isSeller){

            $queryBuilder->andWhere("u.userType = :userType")
                         ->setParameter(":userType", User::USER_TYPE_SELLER);

            $queryBuilder->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
                         ->andWhere("s.storeType = :storeType")
                         ->setParameter(":storeType", $isAffiliate? Store::STORE_TYPE_RESELLER : Store::STORE_TYPE_MERCHANT);
        }
        else{
            $queryBuilder->andWhere("u.userType = :userType")
                         ->setParameter(":userType", User::USER_TYPE_BUYER);
        }

        $queryBuilder->innerJoin("YilinkerCoreBundle:OneTimePassword", "otp", Join::WITH, "otp.user = u")
                     ->andWhere("otp.token = :token")
                     ->setParameter(":token", $token);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Fetch user by email with userid exclusion filter
     *
     * @param string $email
     * @param int $excludeUserId
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     */
    public function findUserByEmailExcludeId(
        $email,
        $excludeUserId = null,
        $excludedUserType = null,
        $userType = null,
        $excludedStoreType = null,
        $storeType = null
    ){
        $queryBuilder = $this->createQueryBuilder('u')
                             ->where('u.email = :email')
                             ->setParameter('email', $email);

        if(!is_null($excludeUserId)){
            $excludeUserIdExpr = $queryBuilder->expr()->neq("u.userId", ":userId");
            $queryBuilder->andWhere($excludeUserIdExpr)->setParameter('userId', $excludeUserId);
        }

        if(!is_null($excludedUserType)){
            $excludedUserTypeExpr = $queryBuilder->expr()->neq("u.userType", ":excludedUserType");
            $queryBuilder->andWhere($excludedUserTypeExpr)->setParameter(":excludedUserType", $excludedUserType);
        }

        if(!is_null($excludedStoreType)){
            $queryBuilder->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = s");
            $excludedStoreTypeExpr = $queryBuilder->expr()->neq("s.storeType", ":excludedStoreType");
            $queryBuilder->andWhere($excludedStoreTypeExpr)->setParameter(":excludedStoreType", $excludedStoreType);
        }

        if (!is_null($userType)) {
            $queryBuilder->andWhere('u.userType = :userType')->setParameter(':userType', $userType);
        }

        if(!is_null($storeType)){
            $queryBuilder->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u");
            $storeTypeExpr = $queryBuilder->expr()->eq("s.storeType", ":storeType");
            $queryBuilder->andWhere($storeTypeExpr)->setParameter(":storeType", $storeType);
        }

        $user = $queryBuilder->setMaxResults(1)
                             ->getQuery()
                             ->getOneOrNullResult();

        return $user;
    }

    public function findGuestByEmailContact($email, $contactNumber)
    {
        $queryBuilder = $this->createQueryBuilder('u')
                             ->where('u.email = :email')
                             ->andWhere('u.contactNumber = :contactNumber')
                             ->andWhere('u.userType = :userType')
                             ->setParameter('email', $email)
                             ->setParameter('contactNumber', $contactNumber)
                             ->setParameter('userType', User::USER_TYPE_GUEST);

        $user = $queryBuilder->getQuery()->getOneOrNullResult();

        return $user;
    }

    public function getConnectedUserWithMessages(User $user, $limit = null, $offset = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("userId", "userId")
            ->addScalarResult("sender", "sender")
            ->addScalarResult("messageId", "messageId")
            ->addScalarResult("message", "message")
            ->addScalarResult("isImage", "isImage")
            ->addScalarResult("lastLoginDate", "lastLoginDate")
            ->addScalarResult("lastMessageDate", "lastMessageDate");

        $sql = "
            SELECT
                s.user_id as userId,
                s.sender_id as sender,
                s.last_logout_date as lastLoginDate,
                s.message_id as messageId,
                s.message as message,
                s.is_image as isImage,
                s.time_sent as lastMessageDate
            FROM (
                SELECT
                    u.user_id,
                    m.sender_id,
                    u.last_login_date,
                    u.last_logout_date,
                    m.message_id,
                    m.message,
                    m.is_image,
                    m.time_sent
                FROM User u
                LEFT JOIN Message m ON m.recipient_id = u.user_id
                OR m.sender_id = u.user_id
                WHERE (
                    m.recipient_id = :user
                    OR m.sender_id = :user
                )
                AND (
                    (m.recipient_id = :user AND m.is_delete_recipient = 0)
                    OR
                    (m.sender_id = :user AND m.is_delete_sender = 0)
                )
                AND NOT u.user_id = :user
                ORDER BY m.message_id DESC
            ) s
            GROUP BY s.user_id
            ORDER BY s.message_id DESC
        ";

        if(!is_null($limit) && !is_null($offset)){
            $sql .= "LIMIT :limit OFFSET :offset";
        }

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter(":user", $user->getUserId());

        if(!is_null($limit) && !is_null($offset)){
            $query->setParameter(":limit", $limit)->setParameter(":offset", $offset);
        }

        return $query->getResult();
    }

    public function isUniqueSlug($slug, User $excludedUser = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $orx = $queryBuilder->expr()->orx();
        $orx->add($queryBuilder->expr()->eq("u.slug", ":slug"));
        $orx->add($queryBuilder->expr()->eq("s.storeSlug", ":slug"));

        $queryBuilder->select("u")
                     ->from("YilinkerCoreBundle:User", "u")
                     ->leftJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
                     ->where($orx)
                     ->setParameter(":slug", $slug);


        if(!is_null($excludedUser)){
            $queryBuilder->andWhere("NOT s.user = :user")
                         ->andWhere("NOT u.slug = :user")
                         ->setParameter(":user", $excludedUser);
        }

        $user = $queryBuilder->getQuery()->getOneOrNullResult();

        if($user){
            return false;
        }

        return true;
    }

    /**
     * @param UserInterface $user
     * @return mixed
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    /**
     * Retrieve registered users
     *
     * @param string $searchKeyword
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $userType
     * @param boolean $isActive
     * @param int $offset
     * @param int $limit
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     */
    public function getRegisteredUser(
        $searchKeyword = null,
        $dateFrom = null,
        $dateTo = null,
        $userType = null,
        $isActive = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT
    )
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('
                          User as userEntity,
                          COUNT(Product.productId) AS numOfUploadedProducts
                     ')
                     ->from('YilinkerCoreBundle:User', 'User')
                     ->leftJoin('YilinkerCoreBundle:Product', 'Product', 'WITH', 'Product.user= User.userId')
                     ->where('User.userId > 0');

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("(CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword OR User.email LIKE :searchKeyword)")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($userType !== null) {
            $queryBuilder->andWhere("User.userType = :userType")
                         ->setParameter('userType', $userType);
        }

        if ($dateFrom !== null) {
            $queryBuilder->andWhere('User.dateAdded >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('User.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo);
        }

        if ($isActive !== null) {
            $queryBuilder->andWhere("User.isActive = :isActive")
                         ->setParameter('isActive', $isActive);
        }

        $queryBuilder->groupBy('User.userId');
        $count = $this->getSellerCount($searchKeyword, $dateFrom, $dateTo, $userType, $isActive);
        $queryBuilder = $queryBuilder->setFirstResult($offset)
                                     ->setMaxResults($limit)
                                     ->getQuery();

        $users = $queryBuilder->getResult();

        return compact('count', 'users');
    }

    public function getSellerCount ($searchKeyword = null, $dateFrom = null, $dateTo = null, $userType = null, $isActive = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('
                          COUNT(User.userId) as cnt
                     ')
                    ->from('YilinkerCoreBundle:User', 'User')
                    ->where('User.userId > 0');

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("(CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword OR User.email LIKE :searchKeyword)")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($userType !== null) {
            $queryBuilder->andWhere("User.userType = :userType")
                         ->setParameter('userType', $userType);
        }

        if ($dateFrom !== null) {
            $queryBuilder->andWhere('User.dateAdded >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('User.dateAdded <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        if($isActive !== null){
            $queryBuilder->andWhere("User.isActive = :isActive")
                         ->setParameter('isActive', $isActive);
        }

        $queryBuilder = $queryBuilder->getQuery();

        return $queryBuilder->getSingleScalarResult();
    }

    /**
     * Retrieve users by store
     *
     * @param string $searchKeyword
     * @param int $storeType
     * @param boolean $isActive
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getUsersByStore($searchKeyword = null, $storeType = null, $isActive = null, $offset = 0, $limit = self::PAGE_LIMIT)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('
                          User as userEntity,
                          COUNT(Product.productId) AS numOfUploadedProducts
                     ')
                     ->from('YilinkerCoreBundle:User', 'User')
                     ->leftJoin('YilinkerCoreBundle:Product', 'Product', 'WITH', 'Product.user= User.userId')
                     ->innerJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId')
                     ->where('User.userId > 0')
                     ->andWhere('User.userType = :userType')
                     ->setParameter('userType', User::USER_TYPE_SELLER);

        if ($searchKeyword !== null) {
            $queryBuilder
                ->andWhere("(CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword OR
                            User.email LIKE :searchKeyword) OR
                            Store.storeName LIKE :searchKeyword")
                ->setParameter('searchKeyword', '%' . $searchKeyword . '%')
            ;
        }

        if ($storeType !== null) {
            $queryBuilder->andWhere("Store.storeType = :storeType")
                         ->setParameter('storeType', $storeType);
        }

        if($isActive !== null){
            $queryBuilder->andWhere("User.isActive = :isActive")
                           ->setParameter('isActive', $isActive);
        }

        $queryBuilder->groupBy('User.userId');
        $count = $this->getStoreCount($searchKeyword, $storeType, $isActive);
        $queryBuilder = $queryBuilder->setFirstResult($offset)
                                     ->setMaxResults($limit)
                                     ->getQuery();

        $users = $queryBuilder->getResult();

        return compact('count', 'users');
    }

    public function getStoreCount ($searchKeyword = null, $storeType = null, $isActive = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('
                          COUNT(User.userId) as cnt
                     ')
                    ->from('YilinkerCoreBundle:User', 'User')
                    ->innerJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId')
                    ->where('User.userId > 0')
                    ->andWhere('User.userType = :userType')
                    ->setParameter('userType', User::USER_TYPE_SELLER);

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("(CONCAT(User.firstName, ' ', User.lastName) LIKE :searchKeyword OR
                                     User.email LIKE :searchKeyword) OR
                                     Store.storeName LIKE :searchKeyword")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%')
            ;
        }

        if ($storeType !== null) {
            $queryBuilder->andWhere("Store.storeType = :storeType")
                         ->setParameter('storeType', $storeType);
        }

        if($isActive !== null){
            $queryBuilder->andWhere("User.isActive = :isActive")
                         ->setParameter('isActive', $isActive);
        }

        $queryBuilder = $queryBuilder->getQuery();

        return $queryBuilder->getSingleScalarResult();
    }

    /**
     * retrieve a user by contact number
     *
     * @param $contactNumber
     * @param $excludeUserId
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     */
    public function findUserByContactNumber(
        $contactNumber,
        $excludeUserId = null,
        $userType = null,
        $storeType = Store::STORE_TYPE_RESELLER
    ){
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('u')
                     ->from('YilinkerCoreBundle:User','u')
                     ->where('u.contactNumber = :contactNumber')
                     ->setParameter('contactNumber', $contactNumber);

        if(is_null($excludeUserId) === false){
            $queryBuilder->andWhere('u.userId <> :userId')
                         ->setParameter('userId', $excludeUserId);
        }

        if (!is_null($userType)) {

            if($userType == User::USER_TYPE_SELLER){
                $queryBuilder->innerJoin('YilinkerCoreBundle:Store', 's', Join::WITH, 's.user = u');

                if($storeType == Store::STORE_TYPE_RESELLER){
                    $orx = $queryBuilder->expr()->orx();

                    $isBuyer = $queryBuilder->expr()->eq('u.userType', User::USER_TYPE_BUYER);

                    $andx = $queryBuilder->expr()->andx();

                    $isSeller = $queryBuilder->expr()->eq('u.userType', User::USER_TYPE_SELLER);
                    $isAffiliate = $queryBuilder->expr()->eq('s.storeType', Store::STORE_TYPE_RESELLER);

                    $andx->add($isSeller)->add($isAffiliate);

                    $orx->add($isBuyer)->add($andx);

                    $queryBuilder->andWhere($orx);
                }
                else{
                    $queryBuilder->andWhere('u.userType = :userType')
                                 ->andWhere('s.storeType = :storeType')
                                 ->setParameter(':userType', User::USER_TYPE_SELLER)
                                 ->setParameter(':storeType', Store::STORE_TYPE_MERCHANT);
                }
            }
            else{
                $queryBuilder->andWhere('u.userType = :userType')
                             ->setParameter(':userType', User::USER_TYPE_BUYER);
            }

        }

        return $queryBuilder->setFirstResult(0)->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * Retrieve number of uploads of a user
     *
     * @param int $userId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param int[] $status
     * @return int
     */
    public function getUserUploadCount(
        $userId,
        DateTime $dateFrom = null,
        DateTime $dateTo = null,
        $status = null,
        $country = null
    ){
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('COUNT(P)')
                     ->from('Yilinker\Bundle\CoreBundle\Entity\Product','P')
                     ->where('P.user = :userId')
                     ->setParameter('userId', $userId);

        if($dateFrom !== null){
            $queryBuilder->andWhere('P.dateCreated >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
        }
        if($dateTo !== null){
            $queryBuilder->andWhere('P.dateCreated <= :dateTo')
                         ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        }

        if ($status !== null) {
            if(is_array($status) === false){
                $status = array($status);
            }

            $queryBuilder->innerJoin(
                "YilinkerCoreBundle:ProductCountry",
                "pc",
                Join::WITH,
                "pc.product = P"
             );
            
            $expr = $queryBuilder->expr()->andx();

            if($country){

                $expr->add($queryBuilder->expr()->eq("pc.country", ":country"))
                     ->add($queryBuilder->expr()->in("pc.status", ":status"));
                
                $queryBuilder->andWhere($expr)
                             ->setParameter(":country", $country)
                             ->setParameter(":status", $status);
            }
            else{
                $expr->add($queryBuilder->expr()->in("pc.status", ":status"));
                $queryBuilder->andWhere($expr)
                    ->setParameter(":status", $status);
            }
        }

        $productCount = $queryBuilder->getQuery()
                                     ->getSingleScalarResult();

        return (int) $productCount;
    }

    /**
     * Retrieves the number of user
     *
     * @param int $type
     * @param boolean $isActive
     * @return int
     */
    public function getNumberOfUsers($type = null, $isActive = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('count(u)')
                     ->from('Yilinker\Bundle\CoreBundle\Entity\User','u');

        if($type !== null){
            $queryBuilder->andWhere('u.userType = :userType')
                         ->setParameter('userType', $type);
        }

        if($isActive !== null){
            $queryBuilder->andWhere('u.isActive = :isActive')
                         ->setParameter('isActive', $isActive);

        }

        $userCount = $queryBuilder->getQuery()
                                  ->getSingleScalarResult();

        return (int) $userCount;
    }

    public function isEmailVerifiedUser($email)
    {
        $this->qb()
             ->andWhere('this.isEmailVerified > :verified')
             ->andWhere('this.email = :email')
             ->andWhere('this.userType != :userType')
             ->setParameter('verified', 0)
             ->setParameter('email', $email)
             ->setParameter('userType', User::USER_TYPE_GUEST)
        ;
        $result = $this->getResult();

        return array_shift($result);
    }

    public function isContactNumberVerifiedUser($contactNumber)
    {
        $this
            ->qb()
            ->andWhere('this.isMobileVerified > :verified')
            ->andWhere('this.contactNumber = :contactNumber')
            ->andWhere('this.userType != :userType')
            ->setParameter('verified', 0)
            ->setParameter('contactNumber', $contactNumber)
            ->setParameter('userType', User::USER_TYPE_GUEST)
        ;
        $result = $this->getResult();

        return array_shift($result);
    }

    public function contactNumberExists($contactNumber, $excludeUserId = null)
    {
        $this
            ->qb()
            ->andWhere('this.contactNumber = :contactNumber')
            ->andWhere('this.userType = :userType')
            ->setParameter('contactNumber', $contactNumber)
            ->setParameter('userType', User::USER_TYPE_BUYER)
        ;
        if ($excludeUserId) {
            $this
                ->andWhere('this.userId <> :userId')
                ->setParameter(':userId', $excludeUserId)
            ;
        }

        return $this->getCount();
    }

    public function emailExists($email)
    {
        $this
            ->qb()
            ->andWhere('this.email = :email')
            ->andWhere('this.userType = :userType')
            ->setParameter('email', $email)
            ->setParameter('userType', User::USER_TYPE_BUYER)
        ;

        return $this->getCount();
    }

    public function filterByStoreType($storeType = Store::STORE_TYPE_RESELLER)
    {
        $this->getQB()
             ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = this")
             ->andWhere("s.storeType = :storeType")
             ->setParameter(":storeType", (int) $storeType === Store::STORE_TYPE_RESELLER
                                          ? Store::STORE_TYPE_RESELLER
                                          : Store::STORE_TYPE_MERCHANT);

        return $this;
    }

    public function filterByUserType($userType = User::USER_TYPE_BUYER)
    {
        $this->getQB()
             ->andWhere("this.userType = :userType")
             ->setParameter(":userType", (int) $userType === User::USER_TYPE_BUYER
                                         ? User::USER_TYPE_BUYER
                                         : User::USER_TYPE_SELLER);

        return $this;
    }

    public function filterByCountry($country)
    {
        $this->getQB()
             ->andWhere("this.country = :country")
             ->setParameter(":country", $country);

        return $this;
    }

    public function filterByUserNameOrContact($request = "")
    {
        $this->getQB()
             ->andWhere($this->getQB()->expr()->orX(
                 $this->getQB()->expr()->eq('this.email', ':request'),
                 $this->getQB()->expr()->eq('this.contactNumber', ':request')
             ))
             ->setParameter('request', trim((string) $request));

        return $this;
    }

    public function filterByEmptyReferralCode()
    {
        $this->getQB()
             ->andWhere($this->getQB()->expr()->orX(
                 $this->getQB()->expr()->isNull('this.referralCode'),
                 $this->getQB()->expr()->eq('this.referralCode', ':emptyString')
             ))
             ->setParameter('emptyString', "");

        return $this;
    }

    /**
     * Get User by referral Code
     *
     * @param $referralCode
     * @param null $excludeUserId
     * @return mixed
     */
    public function getUserByReferralCode ($referralCode, $excludeUserId = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('User')
                     ->from('YilinkerCoreBundle:User','User')
                     ->where('User.referralCode = :referralCode')
                     ->setParameter('referralCode', $referralCode);

        if (!is_null($excludeUserId)) {
            $queryBuilder->andWhere('User.userId != :userId')
                         ->setParameter('userId', $excludeUserId);
        }

        $result = $queryBuilder->getQuery()->getResult();

        return array_shift($result);
    }

    /**
     * Get users grouped by contact number
     *
     * @param int $contactNumberCountMinimum
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function getAffiliateBuyerGroupedByContactNumber($contactNumberCountMinimum = 1, $offset = null, $limit = null)
    {
        $sql = "
            SELECT
                 User.contact_number as contactNumber,
                 COUNT(User.user_id) as userCount,
                 COUNT(DISTINCT IFNULL(User.account_id, 0)) as accountIdCount
            FROM
                 User
            LEFT JOIN
                 Store s ON s.user_id = User.user_id
            WHERE NOT (User.user_type = :userType AND s.store_type = :storeType)
            GROUP BY User.contact_number
            HAVING userCount > :minCount AND accountIdCount > 1
        ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("contactNumber", "contactNumber");
        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter("minCount", $contactNumberCountMinimum);
        $query->setParameter("userType", User::USER_TYPE_SELLER);
        $query->setParameter("storeType", Store::STORE_TYPE_MERCHANT);
        $result = $query->getResult();
        $contactNumbers = array_map(function($value) { return $value['contactNumber']; }, $result);

        $mainQueryBuilder = $this->_em->createQueryBuilder();
        $mainQueryBuilder->select(array('Aggregated.contactNumber', 'Aggregated as user'))
                         ->from('YilinkerCoreBundle:User', 'Aggregated')
                         ->andWhere('Aggregated.contactNumber IN (:contactNumbers)')
                         ->setParameter('contactNumbers', $contactNumbers)
                         ->orderBy('Aggregated.contactNumber');
        if($limit !== null){
            $mainQueryBuilder->setMaxResults($limit);
        }
        if($offset !== null){
            $mainQueryBuilder->setFirstResult($offset);
        }

        $users = $mainQueryBuilder->getQuery()->getResult();
        $resultData = array();
        foreach($users as $user){
            if(isset($resultData[$user['contactNumber']]) === false){
                $resultData[$user['contactNumber']] = new ArrayCollection();
            }
            $resultData[$user['contactNumber']]->add($user['user']);
        }

        return $resultData;
    }

    /**
     * Get buyer/affiliate with non-existent corresponding pair
     *
     * @param int $offset
     * @param int $limit
     * @return Yilinker\Bundle\CoreBundle\Entity\User[]
     */
    public function getAffiliateBuyerWithNoPair($offset, $limit)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder->select('User')
                     ->addSelect("COUNT(User.accountId) as HIDDEN userCount")
                     ->from('YilinkerCoreBundle:User','User')
                     ->leftJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = User")
                     ->having('userCount = 1')
                     ->groupBy('User.accountId');

        $andX = $queryBuilder->expr()->andX();
        $andX->add($queryBuilder->expr()->eq("User.userType", User::USER_TYPE_SELLER));
        $andX->add($queryBuilder->expr()->eq("s.storeType", Store::STORE_TYPE_MERCHANT));
        $queryBuilder->where($queryBuilder->expr()->not($andX));

        if($limit !== null){
            $queryBuilder->setMaxResults($limit);
        }
        if($offset !== null){
            $queryBuilder->setFirstResult($offset);
        }

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function updateUserAccounts(
        $email,
        $contactNumber,
        $password,
        $firstName,
        $lastName,
        $isActive,
        $isEmailVerified,
        $isMobileVerified,
        $isSocialMedia,
        $accountId
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->update("YilinkerCoreBundle:User", "u")
                     ->set("u.email", ":email")
                     ->set("u.contactNumber", ":contactNumber")
                     ->set("u.password", ":password")
                     ->set("u.firstName", ":firstName")
                     ->set("u.lastName", ":lastName")
                     ->set("u.isActive", ":isActive")
                     ->set("u.isEmailVerified", ":isEmailVerified")
                     ->set("u.isMobileVerified", ":isMobileVerified")
                     ->set("u.isSocialMedia", ":isSocialMedia")
                     ->where("u.accountId = :accountId")
                     ->setParameter(":email", $email)
                     ->setParameter(":contactNumber", $contactNumber)
                     ->setParameter(":password", $password)
                     ->setParameter(":firstName", $firstName)
                     ->setParameter(":lastName", $lastName)
                     ->setParameter(":isActive", $isActive)
                     ->setParameter(":isEmailVerified", $isEmailVerified)
                     ->setParameter(":isMobileVerified", $isMobileVerified)
                     ->setParameter(":isSocialMedia", $isSocialMedia)
                     ->setParameter(":accountId", $accountId)
                     ->getQuery()
                     ->execute();
    }


    /**
     * get User either by Id or Slug
     *
     * only for seller/affiliate
     */
    public function getOnebyUserOrSlug($id,$userType=User::USER_TYPE_SELLER)
    {
        $this->qb()
            ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = this");

        $orX = $this->getQB()->expr()->orX();

        $orX->add($this->getQB()->expr()->eq("this.userId", ':id' ));
        $orX->add($this->getQB()->expr()->eq("s.storeSlug", ':id' ));

        $this->where($orX);
        $this->andWhere('this.userType = :userType')
            ->setParameter('userType', $userType)
            ->setParameter('id',$id);

        return $this;
    }

    /**
     * Finds a user by contact number with non-strict rules. Use with care.
     *
     * @param string $contactNumber
     * @param int $userType
     * @return Yilinker\Bundle\CoreBundle\Entity\User
     */
    public function findUserByContactNumberLenient($contactNumber, $userType = User::USER_TYPE_BUYER)
    {
        $queryBuilder = $this->_em
                      ->createQueryBuilder()
                      ->select("u")
                      ->from("YilinkerCoreBundle:User", "u");


        $orX = $queryBuilder->expr()->orx();
        $orX->add($queryBuilder->expr()->eq("u.contactNumber", ':contactNumberNoZero' ));
        $orX->add($queryBuilder->expr()->eq("u.contactNumber", ':contactNumberWIthZero' ));


        $isFirstDigitZero = $contactNumber[0] === "0";
        if($isFirstDigitZero){
            $contactNumberNoZero = subtring($contactNumber, 1, strlen($contactNumber));
            $contactNumberWithZero = $contactNumber;
        }
        else{
            $contactNumberNoZero = $contactNumber;
            $contactNumberWithZero = "0".$contactNumber;
        }

        if($userType !== null){
            $queryBuilder->andWhere("u.userType = :userType")
                         ->setParameter("userType", $userType);

        }

        return $queryBuilder->andWhere($orX)
                     ->setParameter("contactNumberNoZero", $contactNumberNoZero)
                     ->setParameter("contactNumberWIthZero", $contactNumberWithZero)
                     ->getQuery()
                     ->getOneOrNullResult();
    }

    public function getInhouseUser()
    {
        $this
            ->qb()
            ->innerJoin('this.store', 'store')
            ->andWhere('store.isInhouse = 1')
        ;

        return $this->getSingleResult();
    }
}
