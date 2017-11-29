<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api\v3;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\UserWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Carbon\Carbon;

class UserWarehouseApiController extends YilinkerBaseController
{
    /**
     * Warehouse List
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     * )
     */

    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $warehouses = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                         ->getUserWarehouses($this->getUser())
                         ->paginate($request->get('page', 1));
        $data = array();
        $locationService = $this->get('yilinker_core.service.location.location');

        foreach ($warehouses as &$warehouse) {
            $locationService->constructLocationHierarchy($warehouse->getLocation());
            $data[] = array('location'=> $locationService->getLocationDataObject(), 'warehouse' => $warehouse->toArray());
        }

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $data;
        $this->jsonResponse['message'] = 'Warehouse list';

        return $this->jsonResponse();
    }

    /**
     * Warehouse Add/Edit
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     *     parameters={
     *         {"name"="warehouseId", "dataType"="string", "required"=true, "description"="warehouseId"},
     *         {"name"="name", "dataType"="string", "required"=true, "description"="Warehouse name"},
     *         {"name"="address", "dataType"="string", "required"=true, "description"="Warehouse address"},
     *         {"name"="location", "dataType"="string", "required"=true, "description"="Warehouse location"},
     *         {"name"="zipCode", "dataType"="string", "required"=false, "description"="Warehouse zipCode"},
     *     }
     * )
     */
    public function formAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $warehouseId = $request->get('warehouseId',null);

        $formData = array(
            'name' => $request->get('name'),
            'address' => $request->get('address'),
            'location' => $request->get('location'),
            'zipCode' => $request->get('zipCode'),
        );

        $warehouseData = new UserWarehouse;
        $formUrlParameter = array();

        if (!is_null($warehouseId)) {
            $warehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                            ->findOneBy(array(
                                'user' => $this->getUser(),
                                'userWarehouseId' => $warehouseId
                            ));
            if ($warehouse) {
                $formUrlParameter = array('warehouseId' => $warehouse->getUserWarehouseId());
                $warehouseData = $warehouse;
            }
        }

        $form = $this->createForm(
            'user_warehouse',
            $warehouseData
        );

        $form->submit($formData);

        if (!$form->isValid()) {
            $response = array(
                'isSuccessful' => false,
                'data' => '',
                'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
            );
        }

        if ($form->isValid()) {
            $em->persist($warehouseData->setUser($this->getUser()));
            $em->flush();

            $response = array(
                'isSuccessful' => true,
                'message' => !$warehouseId? 'New warehouse successfully added.' : 'Warehouse successfully modified.'
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Warehouse Delete
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     *     parameters={
     *         {"name"="warehouseId", "dataType"="string", "required"=true, "description"="warehouseId"},
     *     }
     * )
     */
    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $warehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                        ->findOneBy(array(
                            'user' => $this->getUser(),
                            'userWarehouseId' => $request->get('warehouseId')
                        ));

        $defaultResponse = array(
            'isSuccessful' => false,
            'message' => 'Can\'t find warehouse.'
        );

        if ($warehouse) {
            $warehouse->setIsDelete(true);

            $productUnitWarehouses = $em->getRepository('YilinkerCoreBundle:ProductUnitWarehouse')
                                        ->findByUserWarehouse($warehouse);

            foreach ($productUnitWarehouses as $productUnit) {
                $productUnit->setQuantity(0);
            }

            $defaultResponse = array(
                'isSuccessful' => true,
                'message' => 'Warehouse has been successfully deleted.'
            );

            $em->flush();
        }

        return new JsonResponse($defaultResponse);
    }

    public function getWarehouse($warehouseId)
    {
        $em = $this->getDoctrine()->getManager();

        $warehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                        ->findOneBy(array(
                            'user' => $this->getUser(),
                            'userWarehouseId' => $warehouseId
                        ));

        if (!$warehouse) {
            /** throw a valid json response instead of redirecting to 404 page */
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Warehouse not found.",
                "data" => array(
                    "errors" => "Warehouse not found."
                )
            ), 404);
        }

        return $warehouse;
    }

    /** @TODO: should refactor */
    public function getInventoryProducts($request, $warehouse, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();

        $productFilter['name'] = $request->get('query', '');

        if ($request->request->has('status') || $request->query->has('status')) {
            $productFilter['statusIn'] = json_decode($request->get('status'));
        }

        if ($request->request->has('group') || $request->query->has('group')) {
            $productFilter['productGroupIn'] = json_decode($request->get('group'));
        }

        if ($request->request->has('category') || $request->query->has('category')) {
            $categories = json_decode($request->get('category'));
            $cList = array();
            foreach ($categories as $category) {
                $c = $em->getRepository("YilinkerCoreBundle:CategoryNestedSet")
                        ->getChildrenCategoryIds($category);
                $cList = array_merge($cList, $c);
            }

            $productFilter['productCategory'] = $cList;
        }

        $inventoryProducts = $em->getRepository('YilinkerCoreBundle:ProductUnit')
                                ->getProductUnitWarehouseByUser($this->getUser(), $warehouse)
                                ->filterByProductIn($productFilter)
                                ->paginate($request->get('page', $page), false);

        return $inventoryProducts;
    }


    /**
     * Warehouse Inventory
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     *     parameters={
     *         {"name"="warehouseId", "dataType"="string", "required"=true, "description"="warehouseId"},
     *         {"name"="page", "dataType"="string", "required"=true, "description"="page"},
     *         {"name"="category", "dataType"="json", "required"=true, "description"="category"},
     *         {"name"="status", "dataType"="json", "required"=true, "description"="status"},
     *         {"name"="query", "dataType"="string", "required"=true, "description"="name"},
     *         {"name"="group", "dataType"="json", "required"=true, "description"="productGroup"},
     *     }
     * )
     */
    public function inventoryAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $inventoryProductsArr = array();

        $warehouse = $this->getWarehouse($request->get('warehouseId'));

        $inventoryProducts = $this->getInventoryProducts($request, $warehouse);

        foreach ($inventoryProducts as $productUnit) {
            $product = $productUnit->getProduct();

            $inventoryProductsArr[] = array(
                'productUnitId' => $productUnit->getProductUnitId(),
                'name' => $product->getName(),
                'sku' => $productUnit->getSku(),
                'quantity' => $productUnit->getWarehouseQuantity($warehouse),
            );
        }

        return new JsonResponse(array(
            'isSuccessful' => true,
            'data'  => array(
                    'inventoryProducts' => $inventoryProductsArr,
                    'totalpage' => ceil($inventoryProducts->count() / $inventoryProducts->getQuery()->getMaxResults()),
                ),

            'message' => 'Successfully retrieved'
        ));
    }

    /**
     * Warehouse Inventory Filter
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     *     parameters={
     *     }
     * )
     */
    public function inventoryFilterAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $categoriesArr = array();
        $productGroups = array();
        $statusArr = array();

        foreach (Product::getProductStatuses() as $i => $status) {
            $statusArr[] = array('id' => $i, 'name' => $status);
        }

        $categories = $em->getRepository("YilinkerCoreBundle:ProductCategory")
                         ->getMainCategories();

        foreach ($categories as &$category) {
            $c = $category->toArray();
            unset($c['parent']);
            unset($c['description']);
            unset($c['hasChildren']);
            $categoriesArr[] = $c;
        }

        foreach ($this->getUser()->getProductGroups() as $group) {
            $productGroups[] = $group->toArray();
        }

        return new JsonResponse(array(
            'isSuccessful' => true,
            'data'  => array(
                    'status' => $statusArr,
                    'categories'    => $categoriesArr,
                    'productGroups' => $productGroups,
                ),

            'message' => 'Successfully retrieved'
        ));
    }

    /**
     * Warehouse Update Quanitty
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Warehouse",
     *     parameters={
     *         {"name"="warehouseId", "dataType"="string", "required"=true, "description"="warehouseId"},
     *         {"name"="productUnit", "dataType"="string", "required"=true, "description"="productUnit"},
     *         {"name"="quantity", "dataType"="string", "required"=true, "description"="quantity"},
     *     }
     * )
     */
    public function updateInventoryAction(Request $request)
    {
        $message = 'Quantity update failed.';
        $isSuccessful = false;
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $tbUserWarehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse');

        $userWarehouse = $tbUserWarehouse
                            ->findOneBy(array(
                                'user' => $user,
                                'userWarehouseId' => $request->get('warehouseId')
                            ));

        $productUnit = $em->find('YilinkerCoreBundle:ProductUnit', $request->get('productUnit'));
        $quantity = $request->get('quantity');

        if (
            $productUnit &&
            $productUnit->getProduct()->getUser()->getUserId() === $user->getUserId() &&
            $userWarehouse &&
            !is_null($quantity)
        ) {

            try{

                $em->beginTransaction();

                $tbUserWarehouse->updateProductUnitWarehouse(
                    $userWarehouse,
                    array($productUnit->getProductUnitId() => $quantity)
                );

                $message = 'Actual inventory value successfully saved.';
                $isSuccessful = true;
                $em->flush();
                $em->commit();

                $productProxy = $productUnit->getProduct();

                if($productProxy){
                    $product = $em->find('YilinkerCoreBundle:Product', $productProxy->getProductId());

                    $productPersister = $this->container->get('fos_elastica.object_persister.yilinker_online.product');
                    $productPersister->insertOne($product);
                }
            }
            catch(\Exception $e){
                $isSuccessful = false;
                $message = 'Something went wrong.';

                if($em->getConnection()->isTransactionActive()){
                    $em->rollback();
                }
            }
        }

        return new JsonResponse(compact('isSuccessful', 'message'));
    }
}
