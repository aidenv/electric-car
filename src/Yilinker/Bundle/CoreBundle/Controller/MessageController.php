<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * Class MessageController
 * @package Yilinker\Bundle\CoreBundle\Controller
 */
class MessageController extends Controller
{
    /**
     * Renders the recent messages
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderRecentMessagesAction($messages)
    {
        return $this->render('YilinkerCoreBundle:Message:recent_messages.html.twig', compact('messages'));
    }

    /**
     * Renders the conversastion
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderConversationAction(Request $request, $isStore = false)
    {
        return $this->render('YilinkerCoreBundle:Message:blank_conversation.html.twig');
    }

    public function renderMessageModalAction(Request $request)
    {
        return $this->render('YilinkerCoreBundle:Message:message_modal.html.twig');
    }

    /**
     * Renders the contacts
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderContactsAction(Request $request, $isStore = false)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $messageService = $this->get('yilinker_core.service.message.chat');

        $page = 1;
        $limit = 10;

        $messageService->setAuthenticatedUser($authenticatedUser);
        $contacts = $messageService->getContacts($request->request->get("keyword", ""), $limit, $page);

        return $this->render('YilinkerCoreBundle:Message:contacts.html.twig', compact('contacts'));
    }

    public function deleteConversationAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $slug = $request->request->get("userId", 0);
            $authenticatedUser = $this->getAuthenticatedUser();

            $entityManager = $this->getDoctrine()->getManager();
            $contactRepository = $entityManager->getRepository("YilinkerCoreBundle:Contact");

            $user = $contactRepository->getUserContactBySlug($authenticatedUser, $slug, true);

            if(is_null($user) || !$user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "User not found.",
                    "data" => array(
                        "errors" => array("User not found.")
                    )
                ), 404);
            }
            
            $messageService = $this->get('yilinker_core.service.message.chat');
            $messageService->deleteConversation($user, $authenticatedUser);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Conversation deleted.",
                "data" => array()
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function setConversationAsReadAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $slug = $request->request->get("userId", 0);
            $authenticatedUser = $this->getAuthenticatedUser();

            $entityManager = $this->getDoctrine()->getManager();
            $contactRepository = $entityManager->getRepository("YilinkerCoreBundle:Contact");

            $user = $contactRepository->getUserContactBySlug($authenticatedUser, $slug, true);

            if(is_null($user) || !$user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "User not found.",
                    "data" => array(
                        "errors" => array("User not found.")
                    )
                ), 404);
            }

            $messageService = $this->get('yilinker_core.service.message.chat');
            $messageService->setAuthenticatedUser($authenticatedUser);
            $messageService->setConversationAsRead($user);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Conversation.",
                "data" => array()
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function getActiveConversationAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $slug = $request->request->get("userId", 0);
            $twig = $this->get("templating");
            $authenticatedUser = $this->getAuthenticatedUser();

            $entityManager = $this->getDoctrine()->getManager();
            $contactRepository = $entityManager->getRepository("YilinkerCoreBundle:Contact");

            $user = $contactRepository->getUserContactBySlug($authenticatedUser, $slug, true);

            if(is_null($user) || !$user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "User not found.",
                    "data" => array(
                        "errors" => array("User not found.")
                    )
                ), 404);
            }
            
            $messageService = $this->get('yilinker_core.service.message.chat');
            $messageService->setAuthenticatedUser($authenticatedUser);
            $contactDetails = $messageService->getContact($user);

            $page = 1;
            $limit = 10;

            $messageService->setConversationAsRead($user);
            $messages = $messageService->getConversationMessages($user, $limit, $page);
            $messages = array_reverse($messages);

            $conversationTemplate = $twig->render('YilinkerCoreBundle:Message:conversation.html.twig', compact('contactDetails', 'messages'));

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Conversation.",
                "data" => array(
                    "template" => $conversationTemplate
                )
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function sendMessageAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $authenticatedUser = $this->getAuthenticatedUser();

            $messageService = $this->get('yilinker_core.service.message.chat');
            $slug = $request->request->get('recipientId', 0);

            $entityManager = $this->getDoctrine()->getEntityManager();
            $contactRepository = $entityManager->getRepository("YilinkerCoreBundle:Contact");

            $recipient = $contactRepository->getUserContactBySlug($authenticatedUser, $slug, false);

            if(is_null($recipient)){
                return $messageService->throwUserNotFound("MESSAGE");
            }

            $postData = array(
                "recipient" => $recipient,
                "message" => $request->request->get('message'),
                "isImage" => $request->request->get('isImage', 0)
            );

            $form = $this->transactForm('message_send', new Message(), $postData);

            if(!$form->isValid()){
                return $messageService->throwInvalidFields("MESSAGE", $form, true);
            }

            $messageService->setAuthenticatedUser($authenticatedUser);

            $data = $messageService->sendMessage($form->getData());

            return new JsonResponse(array(
                "isSuccessful" => true,
                "responseType" => "MESSAGE",
                "message" => "Message successfully sent.",
                "data" => $data
            ), 201);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function getConversationMessagesAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $userId = $request->request->get("userId", 0);
            $page = $request->request->get("page", 1);
            $limit = $request->request->get("limit", 10);
            $excludedTimeSent = $request->request->get("excludedTimeSent", null);

            $twig = $this->get("templating");
            $authenticatedUser = $this->getAuthenticatedUser();

            $entityManager = $this->getDoctrine()->getManager();
            $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");

            $user = $userRepository->find($userId);

            if(is_null($user) || !$user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "User not found.",
                    "data" => array(
                        "errors" => array("User not found.")
                    )
                ), 404);
            }
            
            $messageService = $this->get('yilinker_core.service.message.chat');
            $messageService->setAuthenticatedUser($authenticatedUser);

            $messages = $messageService->getConversationMessages($user, $limit, $page, $excludedTimeSent);
            $messages = array_reverse($messages);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Conversation.",
                "data" => array(
                    "messages" => $messages
                )
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function getConversationHeadAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $page = $request->request->get("page", 1);
            $limit = $request->request->get("limit", 10);

            $authenticatedUser = $this->getAuthenticatedUser();
            $messageService = $this->get('yilinker_core.service.message.chat');

            $messageService->setAuthenticatedUser($authenticatedUser);
            $messages = $messageService->getConversationHead($limit, $page);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Conversation Head.",
                "data" => array(
                    "messages" => $messages
                )
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function getContactsAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $page = $request->request->get("page", 1);
            $limit = $request->request->get("limit", 10);
            $keyword = $request->request->get("keyword", "");
            $keyword = trim($keyword);

            $authenticatedUser = $this->getAuthenticatedUser();
            $messageService = $this->get('yilinker_core.service.message.chat');

            $messageService->setAuthenticatedUser($authenticatedUser);
            $contacts = $messageService->getContacts($keyword, $limit, $page);


            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Contacts.",
                "data" => array(
                    "contacts" => $contacts
                )
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    public function uploadMessageImagesAction(Request $request){
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $authenticatedUser = $this->getAuthenticatedUser();

            $files = $request->files->get("messageImages", array());
            $slug = $request->request->get('recipientId', 0);

            $entityManager = $this->getDoctrine()->getManager();

            if(empty($files)){
                return new JsonResponse(array(
                    "isSuccessful"  => false,
                    "message"       => "No file uploaded.",
                    "data"          => array(
                        "errors"    => array("No file uploaded.")
                    )
                ), 400);
            }

            $contactRepository = $entityManager->getRepository("YilinkerCoreBundle:Contact");

            $recipient = $contactRepository->getUserContactBySlug($authenticatedUser, $slug, false);

            $messageService = $this->get('yilinker_core.service.message.chat');

            if(is_null($recipient)){
                return $messageService->throwUserNotFound("MESSAGE");
            }

            foreach($files as $file){
                $form = $this->transactForm('message_image', null, array("image" => $file));

                if(!$form->isValid()){
                    return new JsonResponse(array(
                        "isSuccessful"  => false,
                        "message"       => "No file uploaded.",
                        "data"          => array(
                            "errors"    => array("No file uploaded.")
                        )
                    ), 400);
                }
            }

            $messageService->setAuthenticatedUser($authenticatedUser);
            $fileNames = $messageService->uploadMessageImages($files);

            $entityManager->beginTransaction();

            try{
                foreach ($fileNames as $fileName) {
                    $message = new Message();
                    $message->setMessage($fileName)
                            ->setRecipient($recipient)
                            ->setIsImage(true);

                    $messageService->sendMessage($message);
                }
            }
            catch(Exception $e){
                $entityManager->rollback();
                return new JsonResponse(array(
                    "isSuccessful"  => false,
                    "message"       => "Failed to upload image.",
                    "data"          => array(
                        "errors" => array(
                            "Failed to upload image.")
                        )
                ), 400);
            }

            $entityManager->commit();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "responseType" => "MESSAGE",
                "message" => "Message successfully sent.",
                "data" => array()
            ), 201);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 400);
        }
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $request
     * @return \Symfony\Component\Form\Form
     * @internal param $postData
     */
    private function transactForm($formType, $entity, $request)
    {
        $form = $this->createForm($formType, $entity);
        $form->submit($request);

        return $form;
    }

    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }
}
