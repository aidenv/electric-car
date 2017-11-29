<?php
namespace Yilinker\Bundle\CoreBundle\Services\UserAddress;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\User;

class UserAddressService
{
    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getUserAddresses(User $user, $orderBy = "ASC")
    {
        $userAddressRepository = $this->em->getRepository("YilinkerCoreBundle:UserAddress");

        $data = array();
        $userAddresses = $userAddressRepository->getUserAddresses($user, $orderBy);

        foreach($userAddresses as $userAddress){
            $location = $userAddress->getLocation();

            $locationDetails = array();
            if(!is_null($location)){
                $locationDetails = $location->getLocalizedLocationTree(true);
            }

            array_push($data, array(
                "userAddressId" => $userAddress->getUserAddressId(),
                "locationId" => !is_null($location)? $location->getLocationId() : null,
                "title" => $userAddress->getTitle(),
                "unitNumber" => $userAddress->getUnitNumber(),
                "buildingName" => $userAddress->getBuildingName(),
                "streetNumber" => $userAddress->getStreetNumber(),
                "streetName" => $userAddress->getStreetName(),
                "subdivision" => $userAddress->getSubdivision(),
                "zipCode" => $userAddress->getZipCode(),
                "streetAddress" => $userAddress->getStreetAddress(),
                "provinceId" => $this->getLocationId("province", $locationDetails),
                "province" => $this->getLocation("province", $locationDetails),
                "cityId" => $this->getLocationId("city", $locationDetails),
                "city" => $this->getLocation("city", $locationDetails),
                "barangayId" => $this->getLocationId("barangay", $locationDetails),
                "barangay" => $this->getLocation("barangay", $locationDetails),
                "longitude" => $userAddress->getLongitude(),
                "latitude" => $userAddress->getLatitude(),
                "landline" => $userAddress->getLandline(),
                "fullLocation" => $userAddress->getAddressString(),
                "isDefault" => $userAddress->getIsDefault(),
            ));
        }

        return $data;
    }

    public function addUserAddress(User $user, UserAddress $userAddress, $location)
    {
        $userAddressRepository = $this->em->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddresses = $userAddressRepository->findBy(array("user" => $user));

        $userAddress->setUser($user)
                    ->setDateAdded(Carbon::now())
                    ->setIsDefault(false);

        if(!is_null($location)){
            $userAddress->setLocation($location);
        }

        if(empty($userAddresses)){
            $userAddress->setIsDefault(true);
        }

        $this->em->persist($userAddress);
        $this->em->flush();

        return $userAddress;
    }

    public function setDefaultUserAddress(UserAddress $userAddress, User $user)
    {
        $userAddressRepository = $this->em->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddressRepository->resetDefaultUserAddress($user);

        $userAddress->setIsDefault(true);
        $this->em->persist($userAddress);
        $this->em->flush();
    }

    public function getDefaultUserAddress(User $user)
    {
        $userAddressRepository = $this->em->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddress = $userAddressRepository->findOneBy(array(
                        "user" => $user,
                        "isDefault" => true
                    ));

        $location = !is_null($userAddress)? $userAddress->getLocation() : null;

        $locationDetails = array();
        if(!is_null($location)){
            $locationDetails = $location->getLocalizedLocationTree(true);
        }

        return array(
            "userAddressId" => !is_null($userAddress)? $userAddress->getUserAddressId() : null,
            "locationId" => !is_null($location)? $location->getLocationId() : null,
            "title" => !is_null($userAddress)? $userAddress->getTitle() : null,
            "unitNumber" => !is_null($userAddress)? $userAddress->getUnitNumber() : null,
            "buildingName" => !is_null($userAddress)? $userAddress->getBuildingName() : null,
            "streetNumber" => !is_null($userAddress)? $userAddress->getStreetNumber() : null,
            "streetName" => !is_null($userAddress)? $userAddress->getStreetName() : null,
            "subdivision" => !is_null($userAddress)? $userAddress->getSubdivision() : null,
            "zipCode" => !is_null($userAddress)? $userAddress->getZipCode() : null,
            "streetAddress" => !is_null($userAddress)? $userAddress->getStreetAddress() : null,
            "provinceId" => $this->getLocationId("province", $locationDetails),
            "province" => $this->getLocation("province", $locationDetails),
            "cityId" => $this->getLocationId("city", $locationDetails),
            "city" => $this->getLocation("city", $locationDetails),
            "barangayId" => $this->getLocationId("barangay", $locationDetails),
            "barangay" => $this->getLocation("barangay", $locationDetails),
            "longitude" => !is_null($userAddress)? $userAddress->getLongitude() : null,
            "latitude" => !is_null($userAddress)? $userAddress->getLatitude() : null,
            "fullLocation" => !is_null($userAddress)? $userAddress->getAddressString() : null,
            "landline" => !is_null($userAddress)? $userAddress->getLandline() : null,
        );
    }

    public function editUserAddress(UserAddress $userAddress, $location)
    {
        if(!is_null($location)){
            $userAddress->setLocation($location);
        }

        $this->em->persist($userAddress);
        $this->em->flush();

        return $userAddress;
    }

    /**
     * Delete an address of a particular user
     *
     * @param int $addressId
     * @param int $userId
     * @return boolean
     */
    public function deleteUserAddress($addressId, $userId)
    {
        $isSuccessful = false;
        $address = $this->em->getRepository('YilinkerCoreBundle:UserAddress')
                        ->getAddressOfUser($userId, $addressId);
        
        if($address){
            /**
             * If address to be deleted is default, set a new default address
             */
            if($address->getIsDefault()){
                $newDefaultAddress = $this->em->getRepository('YilinkerCoreBundle:UserAddress')
                                          ->findOneBy(array('isDefault' => false));
                if($newDefaultAddress){
                    $newDefaultAddress->setIsDefault(true);
                }
            }
            
            $this->em->remove($address);
            $this->em->flush();
            $isSuccessful = true;
        }
            
        return $isSuccessful;
    }

    public function getLocationDetailsArray($locationType, $locationDetails)
    {
        return array(
            "locationId" => array_key_exists($locationType, $locationDetails)? $locationDetails[$locationType]["locationId"] : null,
            "location" => array_key_exists($locationType, $locationDetails)? $locationDetails[$locationType]["location"] : null,
        );
    }

    public function getLocationId($locationType, $locationDetails)
    {
        return array_key_exists($locationType, $locationDetails)? $locationDetails[$locationType]["locationId"] : null;
    }

    public function getLocation($locationType, $locationDetails)
    {
        return array_key_exists($locationType, $locationDetails)? $locationDetails[$locationType]["location"] : null;
    }

    /**
     * Bulk Create, Update or Delete base on:
     *      Create if isNew = true
     *      Update if isNew = false && isChanged = true
     *      SoftDelete is isNew = false && isRemoved = true
     * Used in AccreditationApplication
     *
     * @param array $addresses
     * @param $user
     * @return bool
     */
    public function bulkCreateUpdateOrDelete ($addresses, User $user)
    {
        $userAddressRepository = $this->em->getRepository('YilinkerCoreBundle:UserAddress');

        foreach ($addresses as $address) {
            $isNew = $address['isNew'] === 'true' ? true : false;
            $isChanged = $address['isChanged'] === 'true' ? true : false;
            $isRemoved = $address['isRemoved'] === 'true' ? true : false;

            /**
             * Update
             */
            if (!$isNew && $isChanged) {
                $userAddressEntity = $userAddressRepository->findOneBy(array(
                                                                 'user' => $user->getUserId(),
                                                                 'userAddressId' => $address['id']
                                                             ));
                $locationEntity = $this->em->getRepository('YilinkerCoreBundle:Location')->find($address['locationId']);

                if ($userAddressEntity && $locationEntity) {
                    $isDefault = $address['isDefault'] === 'true';

                    $this->updateAddressDetailed (
                               $userAddressEntity,
                               $locationEntity,
                               $address['addressTitle'],
                               $address['unitNumber'],
                               $address['buildingName'],
                               $address['streetNumber'],
                               $address['streetName'],
                               $address['subdivision'],
                               $address['zipCode'],
                               $isDefault
                           );
                }
            }
            /**
             * Delete
             */
            else if (!$isNew && $isRemoved) {
                $userAddressEntity = $userAddressRepository->findOneBy(array(
                                                                 'user' => $user->getUserId(),
                                                                 'userAddressId' => $address['id']
                                                             ));

                if ($userAddressEntity) {
                    $this->softDelete($userAddressEntity);
                }
            }
            /**
             * Create
             */
            else if ($isNew) {
                $locationEntity = $this->em->getRepository('YilinkerCoreBundle:Location')->find($address['locationId']);
                $isDefault = $address['isDefault'] === 'true';

                if ($locationEntity) {
                    $this->createAddressDetailed (
                               $user,
                               $locationEntity,
                               $address['addressTitle'],
                               $address['unitNumber'],
                               $address['buildingName'],
                               $address['streetNumber'],
                               $address['streetName'],
                               $address['subdivision'],
                               $address['zipCode'],
                               $isDefault
                           );
                }
            };
        }

        return true;
    }

    /**
     * Set IsDelete to 1 (Deleted)
     *
     * @param UserAddress $userAddress
     * @return UserAddress
     */
    public function softDelete (UserAddress $userAddress)
    {
        $userAddress->setIsDelete(UserAddress::STATUS_DELETED);
        $this->em->flush();

        return $userAddress;
    }

    /**
     * Update User Address Detailed
     *
     * @param UserAddress $userAddress
     * @param Location $location
     * @param $title
     * @param $unitNumber
     * @param $buildingName
     * @param $streetNumber
     * @param $streetName
     * @param $subdivision
     * @param $zipCode
     * @param $isDefault
     * @return UserAddress
     */
    public function updateAddressDetailed (
        UserAddress $userAddress,
        Location $location,
        $title,
        $unitNumber,
        $buildingName,
        $streetNumber,
        $streetName,
        $subdivision,
        $zipCode,
        $isDefault
    )
    {
        $userAddress->setLocation($location);
        $userAddress->setTitle($title);
        $userAddress->setUnitNumber($unitNumber);
        $userAddress->setBuildingName($buildingName);
        $userAddress->setStreetNumber($streetNumber);
        $userAddress->setStreetName($streetName);
        $userAddress->setSubdivision($subdivision);
        $userAddress->setZipCode($zipCode);
        $userAddress->setIsDefault($isDefault);
        $this->em->flush();

        return $userAddress;
    }

    /**
     * Create UserAddressDetailed
     *
     * @param User $user
     * @param Location $location
     * @param $title
     * @param $unitNumber
     * @param $buildingName
     * @param $streetNumber
     * @param $streetName
     * @param $subdivision
     * @param $zipCode
     * @param $isDefault
     * @return UserAddress
     */
    public function createAddressDetailed (
        User $user,
        Location $location,
        $title,
        $unitNumber,
        $buildingName,
        $streetNumber,
        $streetName,
        $subdivision,
        $zipCode,
        $isDefault
    )
    {
        $userAddress = new UserAddress();
        $userAddress->setUser($user);
        $userAddress->setLocation($location);
        $userAddress->setTitle($title);
        $userAddress->setUnitNumber($unitNumber);
        $userAddress->setBuildingName($buildingName);
        $userAddress->setStreetNumber($streetNumber);
        $userAddress->setStreetName($streetName);
        $userAddress->setSubdivision($subdivision);
        $userAddress->setZipCode($zipCode);
        $userAddress->setDateAdded(Carbon::now());
        $userAddress->setIsDelete(UserAddress::STATUS_ACTIVE);
        $userAddress->setIsDefault($isDefault);

        $this->em->persist($userAddress);
        $this->em->flush();

        return $userAddress;
    }
}
