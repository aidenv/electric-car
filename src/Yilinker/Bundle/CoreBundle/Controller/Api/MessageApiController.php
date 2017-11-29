<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Message;
use Yilinker\Bundle\CoreBundle\Form\Type\MessageSendFormType;

class MessageApiController extends Controller
{
    /**
     * Sends message to other user
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function sendMessageAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $recipient = $entityManager->getRepository('YilinkerCoreBundle:User')
                                   ->find($request->request->get('recipientId', 0));

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

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());

        $data = $messageService->sendMessage($form->getData());

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "MESSAGE",
            "message" => "Message successfully sent.",
            "data" => $data
        ), 201);
    }

    /**
     * Marks the messages of the sender as read
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function setConversationAsReadAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('YilinkerCoreBundle:User')
                              ->find($request->request->get('userId', 0));

        if(is_null($user)){
            return $messageService->throwUserNotFound("CONVERSATION_AS_READ");
        }

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());
        $messageService->setConversationAsRead($user);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "CONVERSATION_AS_READ",
            "message" => "Conversation successfully marked as read.",
            "data" => array()
        ), 200);
    }

    /**
     * Get contacts
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function getConversationMessagesAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('YilinkerCoreBundle:User')
                              ->find($request->request->get('userId', 0));

        if(is_null($user)){
            return $messageService->throwUserNotFound("CONVERSATION_MESSAGES");
        }

        $page = (int)$request->request->get("page");
        $limit = (int)$request->request->get("limit");

        if($page < 1 OR $limit < 1){
            return $messageService->throwInvalidFields("CONVERSATION_MESSAGES", null, false, array("Invalid limit or offset supplied"));
        }

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());
        $messages = $messageService->getConversationMessages($user, $limit, $page);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "CONVERSATION_MESSAGES",
            "message" => "Successfully fetched messages.",
            "data" => $messages
        ), 200);
    }

    /**
     * Get contacts
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function getContactsAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $page = (int)$request->request->get("page");
        $limit = (int)$request->request->get("limit");

        if($page < 1 OR $limit < 1){
            return $messageService->throwInvalidFields("CONTACTS", null, false, array("Invalid limit or offset supplied"));
        }

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());
        $contacts = $messageService->getContacts($request->request->get("keyword", ""), $limit, $page);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "CONTACTS",
            "message" => "Successfully fetched contacts.",
            "data" => $contacts
        ), 200);
    }

    /**
     * Get conversation head
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function getConversationHeadAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $page = (int)$request->request->get("page");
        $limit = (int)$request->request->get("limit");

        if($page < 1 OR $limit < 1){
            return $messageService->throwInvalidFields("CONVERSATION_HEAD", null, false, array("Invalid limit or offset supplied"));
        }

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());
        $messages = $messageService->getConversationHead($limit, $page);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "CONVERSATION_HEAD",
            "message" => "Successfully fetched conversation head.",
            "data" => $messages
        ), 200);
    }

    /**
     * Uploads the image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function imageAttachAction(Request $request)
    {
        $messageService = $this->get('yilinker_core.service.message.chat');

        $image = $request->files->get('image', null);
        //check if image field is included in the form
        if($image == null){
            return $messageService->throwInvalidFields("IMAGE_UPLOAD", null, false, array("Invalid fields supplied."));
        }

        $postData = array("image" => $image);

        $form = $this->transactForm('message_image', null, $postData);

        if(!$form->isValid()){
            return $messageService->throwInvalidFields("IMAGE_UPLOAD", $form, true);
        }

        $file = $form->getData()["image"];

        $messageService->setAuthenticatedUser($this->getAuthenticatedUser());
        $data = $messageService->uploadImage($file);

        if(!$data){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "responseType" => "IMAGE_UPLOAD",
                "message" => "Image exceeds maximum file size.",
                "data" => array()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "IMAGE_UPLOAD",
            "message" => "Image successfully uploaded.",
            "data" => $data
        ), 201);
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $postData
     * @return \Symfony\Component\Form\Form
     */
    private function transactForm($formType, $entity, $postData)
    {
        $form = $this->createForm($formType, $entity);
        $form->submit($postData);

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
