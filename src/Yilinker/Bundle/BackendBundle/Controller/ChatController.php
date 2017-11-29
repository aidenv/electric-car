<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ChatController extends Controller
{
    public function renderChatRoomAction()
    {
        return $this->render('YilinkerBackendBundle:Chat:chatroom.html.twig');
    }

    public function renderTicketAction()
    {
        return $this->render('YilinkerBackendBundle:Chat:ticket.html.twig');
    }

    public function renderChatSideBarAction()
    {
        return $this->render('YilinkerBackendBundle:Chat:base/sidebar.html.twig');
    }

}
