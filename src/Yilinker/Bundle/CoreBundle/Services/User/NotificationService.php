<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserNotification;
use Symfony\Component\Yaml\Parser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\ORM\PersistentCollection;

class NotificationService
{
    private $container;
    private $em;
    private $config;

    public function __construct($container)
    {
        $this->container = $container;
        $this->setConfig();
    }

    public function setConfig($path = '')
    {
        $path = $path ? $path: __DIR__.'/../../Resources/config/notifications.yml';
        $yaml = new Parser;
        $config = $yaml->parse(file_get_contents($path));
        $this->config = new ParameterBag($config);
    }

    public function getConfig($key, $default = false)
    {
        if (!$key) {
            return $this->config->all();
        }

        return $this->config->get($key, $default, true);
    }

    public function setEntityManager($em)
    {
        $this->em = $em;
    }

    public function isLoggable($entity, $metadata, $mysqlAction)
    {
        $table = $metadata->table['name'];
        $exists = $this->getConfig('tables['.$table.']['.$mysqlAction.']');
        $isLoggable = $exists !== false;
        if ($isLoggable && $entity && is_array($exists) && array_key_exists('loggable', $exists)) {
            $loggable = $exists['loggable'];
            if (is_string($loggable)) {
                $repo = $this->em->getRepository($metadata->getName());
                if (method_exists($repo, $loggable)) {
                    $loggable = call_user_func_array(array($repo, $loggable), array($entity));
                }
            }

            return $loggable;
        }

        return $isLoggable;
    }

    public function getUserKeys($table, $mysqlAction = 'INSERT')
    {
        $userkeys = $this->getConfig('tables['.$table.'][user]');
        $actionUserKeys = $this->getConfig('tables['.$table.']['.$mysqlAction.'][user]');

        $userkeys = $actionUserKeys ? $actionUserKeys: $userkeys;
        $userkeys = !is_array($userkeys) ? array($userkeys): $userkeys;

        return $userkeys;
    }

    public function record(LifecycleEventArgs $args, $mysqlAction)
    {
        $this->setEntityManager($args->getEntityManager());
        $entity = $args->getEntity();
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $table = $metadata->table['name'];

        if (!$this->isLoggable($entity, $metadata, $mysqlAction)) {
            return false;
        }

        $entityService = $this->container->get('yilinker_core.service.entity');
        $associations = $this->getConfig('tables['.$table.'][associations]', array());
        $data = $entityService->toArray($entity, $associations);
        if ($mysqlAction == 'UPDATE') {
            $fields = $this->getConfig('tables['.$table.'][fields]', array());
            $fields = array_keys($fields);
            $changes = $entityService->getChanges($entity, $fields);
            if ($changes) {
                $data['__changes'] = $changes;
            }
        }
        $userkeys = $this->getUserKeys($table, $mysqlAction);

        $usersNotified = array();
        foreach ($userkeys as $key => $userkey) {
            if (!is_numeric($key)) {
                if (!$entityService->compareOr($entity, $userkey)) {
                    continue;
                }
                $userkey = $key;
            }
            $users = $entityService->getValue($entity, $userkey);
            if (!is_array($users)) {
                $users = array($users);
            }
            foreach ($users as $user) {
                if (
                    $user instanceof User &&
                    !in_array($user->getUserId(), $usersNotified)
                ) {
                    $usersNotified[] = $user->getId();

                    $customEm = $this->container->get('doctrine')->getManager('custom');
                    $customEm->clear();
                    $notification = new UserNotification;
                    $notification->setAffectedTable($table);
                    $notification->setData($data);
                    $notification->setMysqlAction($mysqlAction);
                    $userProxy = $customEm->getReference('YilinkerCoreBundle:User', $user->getUserId());
                    $notification->setUser($userProxy);

                    $customEm->persist($notification);
                    $customEm->flush();
                    $this->notify($notification);
                }
            }
        }
    }

    public function recordNotification($entity, $mysqlAction)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $table = $metadata->table['name'];

        $entityService = $this->container->get('yilinker_core.service.entity');
        $associations = $this->getConfig('tables['.$table.'][associations]', array());
        $data = $entityService->toArray($entity, $associations);

        if ($mysqlAction == 'UPDATE') {
            $fields = $this->getConfig('tables['.$table.'][fields]', array());
            $fields = array_keys($fields);
            $changes = $entityService->getChanges($entity, $fields);
            if ($changes) {
                $data['__changes'] = $changes;
            }
        }

        $userkeys = $this->getUserKeys($table, $mysqlAction);

        $usersNotified = array();

        foreach ($userkeys as $key => $userkey) {

            if (!is_numeric($key)) {
                if (!$entityService->compareOr($entity, $userkey)) {
                    continue;
                }
                $userkey = $key;
            }

            $users = $entityService->getValue($entity, $userkey);

            if (!is_array($users)) {
                $users = array($users);
            }

            foreach ($users as $user) {
                if (
                    $user instanceof User &&
                    !in_array($user->getUserId(), $usersNotified)
                ) {
                    $usersNotified[] = $user->getId();

                    $customEm = $this->container->get('doctrine')->getManager('custom');
                    $customEm->clear();
                    $notification = new UserNotification;
                    $notification->setAffectedTable($table);
                    $notification->setData($data);
                    $notification->setMysqlAction($mysqlAction);
                    $userProxy = $customEm->getReference('YilinkerCoreBundle:User', $user->getUserId());
                    $notification->setUser($userProxy);

                    $customEm->persist($notification);
                    $customEm->flush();
                    $this->notify($notification);
                }
            }
        }
    }

    public function sendNotification(LifecycleEventArgs $args, $mysqlAction)
    {
        $this->setEntityManager($args->getEntityManager());
        $entity = $args->getEntity();
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $table = $metadata->table['name'];

        if (!$this->isLoggable($entity, $metadata, $mysqlAction)) {
            return false;
        }

        $entityService = $this->container->get('yilinker_core.service.entity');
        $associations = $this->getConfig('tables['.$table.'][associations]', array());
        $includes = $this->getConfig('tables['.$table.'][includes]', array());
        $data = $entityService->toArray($entity, $associations, $includes);
        if ($mysqlAction == 'UPDATE') {
            $fields = $this->getConfig('tables['.$table.'][fields]', array());
            $fields = array_keys($fields);
            $changes = $entityService->getChanges($entity, $fields);
            if ($changes) {
                $data['__changes'] = $changes;
            }
        }

        $userkeys = $this->getUserKeys($table, $mysqlAction);

        $usersNotified = array();
        $smsNotif = $this->container->get('yilinker_core.service.log.user.sms_notification');
        foreach ($userkeys as $key => $userkey) {
            if (!is_numeric($key)) {
                if (!$entityService->compareOr($entity, $userkey)) {
                    continue;
                }
                $userkey = $key;
            }
            $users = $entityService->getValue($entity, $userkey);
            if (!is_array($users)) {
                $users = array($users);
            }
            foreach ($users as $user) {
                if ($user instanceof User && !in_array($user->getUserId(), $usersNotified)) {
                    $usersNotified[] = $user->getId();

                    // send email
                    if ($user->getEmail()) {
                        $action = $mysqlAction != 'INSERT' ? '.'.$mysqlAction: '';
                        $template = $table.$action.'.html.twig';
                        $message = \Swift_Message::newInstance()
                            ->setSubject($smsNotif->subject($table, $mysqlAction, $user, $data))
                            ->setFrom('noreply@easyshop.ph')
                            ->setTo($user->getEmail())
                            ->setBody(
                                $this->container->get('twig')->render(
                                    'YilinkerCoreBundle:EmailNotifications:'.$template,
                                    compact('user', 'data')
                                ),
                                'text/html'
                            )
                        ;
                        $this->container->get('swiftmailer.mailer.transaction')->send($message);
                    }

                    // send sms
                    $smsNotif->send($table, $mysqlAction, $user, $data);
                }
            }
        }
    }

    public function notify($notification)
    {
        /**
         * TODO: fix the memory problem here first
         */
        return true;
        
        $kernelContainer = $this->container->get('kernel')->getContainer();
        $socket = $this->container->get('yilinker_core.service.node.socket');
        $host = $kernelContainer->getParameter('node_host');
        $port = $kernelContainer->getParameter('node_port');
        $socket->connect('http://'.$host.':'.$port);
        $data = array(
            's' => $notification->getUser()->hashkey(),
            'notification' => $this->getTemplate($notification)
        );

        $socket->emit('notification', $data);
    }

    public function getTemplate($notification)
    {
        $twig = $this->container->get('twig');
        $action = $notification->getMysqlAction();
        $action = $action != 'INSERT' ? '.'.$action: '';
        $template = $notification->getAffectedTable().$action.'.html.twig';
        $data = compact('notification');

        return $twig->render('YilinkerCoreBundle:Notifications:'.$template, $data);
    }
}
