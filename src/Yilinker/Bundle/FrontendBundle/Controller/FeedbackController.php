<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController as Controller;
use Symfony\Component\HttpFoundation\Request;

class FeedbackController extends Controller
{
    public function productFeedbackAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $orderProductId = $request->get('orderProductId');
        $productId = $request->get('productId');
        $rating = $request->get('rating');
        $title = $request->get('title');
        $review = $request->get('review');

        $orderProduct = $em->getReference('YilinkerCoreBundle:OrderProduct', $orderProductId);
        $product = $em->getReference('YilinkerCoreBundle:Product', $productId);

        $productReview = new ProductReview;
        $productReview->setReviewer($this->getUser());
        $productReview->setProduct($product);
        $productReview->setOrderProduct($orderProduct);
        $productReview->setTitle($title);
        $productReview->setReview($review);
        $productReview->setIsHidden(false);
        $productReview->setRating($rating);

        $em->persist($productReview);
        $em->flush();

        return $this->redirectBack();
    }

    public function sellerFeedbackFormAction(Request $request)
    {
        $form = $this->createForm('seller_feedback', null, array(
            'action' => $this->generateUrl('feedback_seller_form')
        ));
        $form->handleRequest($request);
        if ($form->isValid() && $request->isMethod('POST')) {
            $em = $this->getDoctrine()->getEntityManager();
            $userFeedback = $form->getData();
            $em->persist($userFeedback);
            $em->flush();

            return $this->redirectBack();
        }
        $form = $form->createView();
        $data = compact('form');

        return $this->render('YilinkerFrontendBundle:Profile:seller_feedback_form.html.twig', $data);
    }
}