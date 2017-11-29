<?php

namespace Yilinker\Bundle\BackendBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\PackageHistory;
use Yilinker\Bundle\CoreBundle\Entity\PackageStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitInventoryHistory;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Doctrine\Common\Collections\Criteria;
use Carbon\Carbon;

class ExpressApiController extends Controller
{

    /**
     * Update package detail
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePackageDetailAction(Request $request)
    {
        $response = array(
            'message' => '',
            'isSuccessful' => false,
            'data' => array(), 
        ); 

        $waybillNumber = $request->get('waybillNumber');
        $packageStatus = $request->get('packageStatus');
        $dateUpdated = $request->get('date');
        $personFullname = $request->get('personInCharge');
        $contactNumber = $request->get('contactNumber');
        $address = $request->get('address');

        $form = $this->createForm('package_status_update', null);
        $form->submit(array(
            'package'        => $waybillNumber,
            'packageStatus'  => $packageStatus,
            'dateUpdated'    => $dateUpdated,
            'personFullname' => $personFullname,
            'contactNumber'  => $contactNumber,
            'address'        => $address,
        ));

        if($form->isValid()){

            $em = $this->getDoctrine()->getManager();
            $formData = $form->getData();
            $package = $formData['package'];
            $package->setPackageStatus($formData['packageStatus']);
            $package->setDateLastModified($formData['dateUpdated']);
            
            $packageHistory = new PackageHistory();
            $packageHistory->setPackageStatus($formData['packageStatus']);
            $packageHistory->setPackage($package);
            $em->persist($packageHistory);
            
            $packageHistory->setDateAdded($formData['dateUpdated']);
            if($formData['personFullname']){
                $packageHistory->setPersonInCharge($formData['personFullname']);
            }
            if($formData['address']){
                $packageHistory->setAddress($formData['address']);
            }
            if($formData['contactNumber']){
                $packageHistory->setContactNumber($formData['contactNumber']);
            }

            $orderProductStatus = null;
            if($formData['packageStatus']->getPackageStatusId() === PackageStatus::STATUS_CHECKED_IN_BY_RIDER_FOR_DELIVERY){
                $orderProductStatus = $em->getReference(
                    'YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_PRODUCT_ON_DELIVERY
                );
            }
            else if($formData['packageStatus']->getPackageStatusId() === PackageStatus::STATUS_RECEIVED_BY_RECIPIENT){
                $orderProductStatus = $em->getReference(
                    'YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_ITEM_RECEIVED_BY_BUYER
                );
            }

            if($orderProductStatus){
                $packageDetails = $package->getPackageDetails();
                foreach($packageDetails as $packageDetail){
                    $orderProduct = $packageDetail->getOrderProduct();
                    $orderProduct->setOrderProductStatus($orderProductStatus);
                }
            }

            $em->flush();

            /**
             * Reset order product history date based on YLX time.
             */
            if($orderProductStatus){
                $packageDetails = $package->getPackageDetails();
                foreach($packageDetails as $packageDetail){
                    $criteria = Criteria::create()
                                        ->where(Criteria::expr()->eq("orderProductStatus", $orderProductStatus));
                    $history = $orderProduct->getOrderProductHistories()
                                            ->matching($criteria)
                                            ->first();
                    $history->setDateAdded($formData['dateUpdated']);                    
                }
            }
            $em->flush();

            $response['message'] = "Package status successfully updated";
            $response['isSuccessful'] = true;
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
            $response['data'] = array(
                'waybillNumber' => $waybillNumber,
                'packageStatus' => $packageStatus,
            );
            $this->get('yilinker_core.express_api_logger')->getLogger()->err(json_encode($response));
        }

        return new JsonResponse($response);
    }

    /**
     * Update inventory status
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateProductInventoryAction(Request $request)
    {
        $response = array(
            'message' => '',
            'isSuccessful' => false,
            'data' => array(), 
        );

        $products = $request->get('product', array());
        $manufacturerProductUnitIds = array();
        $indexedCombinationData = array();
        foreach($products as $product){
            foreach($product['combination'] as $combination){
                $manufacturerProductUnitIds[] = $combination['combinationId'];
                $indexedCombinationData[$combination['combinationId']] = $combination;
            }
        }
        
        $em = $this->getDoctrine()->getManager();
        $manufacturerProductUnits = $em->getRepository('YilinkerCoreBundle:ManufacturerProductUnit')
                                   ->getManufacturerProductUnitsIn($manufacturerProductUnitIds);

        $updatedProducts = array();
        foreach($manufacturerProductUnits as $manufacturerProductUnit){

            $manufacturerProductUnitId = $manufacturerProductUnit->getManufacturerProductUnitId();
            $manufacturerProduct = $manufacturerProductUnit->getManufacturerProduct();           
            $referenceNumber = $manufacturerProduct->getReferenceNumber();
            $productId = $manufacturerProduct->getManufacturerProductId();
            $combination = $indexedCombinationData[$manufacturerProductUnitId];

            if(isset($updatedProducts[$productId]) == false){
                $updatedProducts[$productId] = array(
                    'productId'       => $productId,
                    'referenceNumber' => $referenceNumber,
                    'combinations'    => array(),
                );
            }

            $dateReceived = Carbon::createFromFormat('Y-m-d H:i:s', $combination['dateReceived']);
            $manufacturerProductUnit->setQuantity($combination['inventory']);
            $manufacturerProductUnit->setDateLastModified($dateReceived);
            $manufacturerProductUnit->setIsInventoryConfirmed(true);
            $inventoryHistory = new ManufacturerProductUnitInventoryHistory();
            $inventoryHistory->setManufacturerProductUnit($manufacturerProductUnit);
            $inventoryHistory->setDateCreated($dateReceived);
            $inventoryHistory->setQuantity($combination['inventory']);
            $em->persist($inventoryHistory);
            
            $updatedProducts[$productId]['combinations'][] = array(
                'inventory'     => $combination['inventory'],
                'combinationId' => $manufacturerProductUnitId,
            );

            if((int) $combination['inventory'] > 0){
                $manufacturerProduct->setStatus(ManufacturerProduct::STATUS_ACTIVE);
            }

            if(count($updatedProducts[$productId]['combinations']) === 0){
                unset($updatedProducts[$productId]);
            }

        }
        
        if(count($updatedProducts) > 0){
            $em->flush();
            $response['data'] = array_values($updatedProducts);
            $response['message'] = 'Product inventory confirmed';
            $response['isSuccessful'] = true;
        }

        return new JsonResponse($response);
    }

    /**
     * receive product list
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse;
     */
    public function receiveProductListAction(Request $request)
    {        
        $limit = $request->get("limit", 10);
        $offset = $request->get("offset", 0);
        $entityManager =  $this->container->get('doctrine')->getManager();
        $manufacturerProducts = $entityManager->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                                              ->getActiveManufacturerProducts(
                                                  null, null, null, null, null,
                                                  $offset, $limit
                                              );

        $logisticService =  $this->container->get('yilinker_core.logistics.yilinker.express');
        $response = $logisticService->forwardProductList($manufacturerProducts);

        return new JsonResponse($response);
    }
    
}
