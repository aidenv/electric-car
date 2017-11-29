<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\UserWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse;

class UserWarehouseController extends Controller
{
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $warehouses = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                         ->getUserWarehouses($this->getUser())
                         ->paginate($request->get('page', 1));

        $data = compact('warehouses');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_warehouse.html.twig', $data);
    }

    public function formAction($warehouseId = null, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

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
            $warehouseData,
            array('action' => $this->generateUrl('merchant_user_warehouse_form', $formUrlParameter))
        );

        $form->handleRequest($request);

        if ($request->isMethod('POST')
            && $form->isValid()) {

            $em->persist($warehouseData->setUser($this->getUser()));
            $em->flush();

            return new JsonResponse(array(
                'isSuccessful' => true,
                'message' => is_null($warehouseId)
                             ? 'New warehouse successfully added.'
                             : 'Warehouse successfully modified.'
            ));
        }

        return $this->render('YilinkerMerchantBundle:Dashboard/Partial:partial.user_warehouse_form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function deleteAction($warehouseId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $warehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                        ->findOneBy(array(
                            'user' => $this->getUser(),
                            'userWarehouseId' => $warehouseId
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
            throw $this->createNotFoundException();
        }

        return $warehouse;
    }

    public function getInventoryProducts($request, $warehouse, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $request->get('category', '');
        $productFilter = array(
            'status' => $request->get('status', ''),
            'name' => $request->get('query', ''),
            'productGroup' => $request->get('group', '')
        );

        if ($request->query->has('category') && strlen($category)) {
            $productFilter['productCategory'] = $em->getRepository("YilinkerCoreBundle:CategoryNestedSet")
                                                   ->getAllCategoriesByCategoryId($category);
        }

        $inventoryProducts = $em->getRepository('YilinkerCoreBundle:ProductUnit')
                                ->getProductUnitWarehouseByUser($this->getUser(), $warehouse)
                                ->filterByProduct($productFilter)
                                ->paginate($request->get('page', $page), false);

        return $inventoryProducts;
    }

    public function exportAction($warehouseId, Request $request)
    {
        $controller = $this;
        $warehouse = $controller->getWarehouse($warehouseId);

        $response = new StreamedResponse();
        $response->setCallback(function() use ($controller, $warehouse, $request){
     
            $handle = fopen('php://output', 'w+');
     
            fputcsv($handle, array('SKU', 'Product Name', 'System Inventory', 'Actual Inventory'));
            $page = 1;
            $productUnits = $controller->getInventoryProducts($request, $warehouse, $page++);
            while (count($productUnits->getIterator())) {
                foreach ($productUnits as $productUnit) {
                    $product = $productUnit->getProduct();
                    $quantity = $productUnit->getWarehouseQuantity($warehouse);

                    fputcsv(
                        $handle,
                        array(
                            $productUnit->getSku(),
                            $product->getName(),
                            $quantity,
                            $quantity
                        )
                    );
                }
                flush();
                $productUnits = $controller->getInventoryProducts($request, $warehouse, $page++);
            }
     
            fclose($handle);
        });
     
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $name = $warehouse->getName().' Inventory '.date('m/d/Y');
        $response->headers->set('Content-Disposition','attachment; filename="'.$name.'.csv"');
     
        return $response;
    }

    public function importAction($warehouseId, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbProductUnit = $em->getRepository('YilinkerCoreBundle:ProductUnit');

        $updateData = $request->get('updateData', array());
        $skus = array_keys($updateData);
        $productUnits = $tbProductUnit->getProductUnitByUserSkus($this->getUser(), $skus)->getResult();
        $updateDataIds = array();
        foreach ($productUnits as $productUnit) {
            $updateDataIds[$productUnit->getProductUnitId()] = $updateData[$productUnit->getSku()];
        }

        if ($updateDataIds) {
            $warehouse = $this->getWarehouse($warehouseId);
            $tbUserWarehouse = $em->getRepository('YilinkerCoreBundle:UserWarehouse');
            $tbUserWarehouse->updateProductUnitWarehouse($warehouse, $updateDataIds);
        }

        return new JsonResponse();
    }

    public function inventoryAction($warehouseId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $productStatuses = Product::getProductStatuses();
        $categories = $em->getRepository("YilinkerCoreBundle:ProductCategory")
                         ->getMainCategories();

        $warehouse = $this->getWarehouse($warehouseId);
        $inventoryProducts = $this->getInventoryProducts($request, $warehouse);

        $data = compact('warehouse', 'inventoryProducts', 'productStatuses', 'categories');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_product_inventory.html.twig', $data);
    }

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
                                'userWarehouseId' => $request->get('userWarehouse')
                            ));

        $productUnit = $em->find('YilinkerCoreBundle:ProductUnit', $request->get('productUnit'));
        $quantity = $request->get('quantity');

        if ($productUnit
            && $productUnit->getProduct()->getUser()->getUserId() === $user->getUserId()
            && $userWarehouse && !is_null($quantity)) {
            $tbUserWarehouse->updateProductUnitWarehouse(
                $userWarehouse,
                array($productUnit->getProductUnitId() => $quantity)
            );

            $message = 'Actual inventory value successfully saved.';
            $isSuccessful = true;

            $em->flush();
        }

        return new JsonResponse(compact('isSuccessful', 'message'));
    }
}
