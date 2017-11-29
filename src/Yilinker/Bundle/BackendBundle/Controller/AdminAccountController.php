<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;

/**
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminAccountController extends Controller
{

    /**
     * Render Register Account
     */
    public function renderRegisterAccountAction ()
    {
        $em = $this->getDoctrine()->getManager();
        $adminUserEntities = $em->getRepository('YilinkerCoreBundle:AdminUser')->findAll();
        $adminUserArray = array();

        if ($adminUserEntities) {

            foreach ($adminUserEntities as $adminUserEntity) {

                $adminUserArray[] = array(
                    'id' => $adminUserEntity->getAdminUserId(),
                    'username' => $adminUserEntity->getUsername(),
                    'firstName' => $adminUserEntity->getFirstName(),
                    'lastName' =>  $adminUserEntity->getLastName(),
                    'role' => $adminUserEntity->getAdminRole()->getName(),
                    'adminRoleId' => $adminUserEntity->getAdminRole()->getAdminRoleId(),
                    'isActive' => $adminUserEntity->getIsActive()
                );

            }

        }

        $registerData = array(
            'adminRoleEntities' => $em->getRepository('YilinkerCoreBundle:AdminRole')->findAll(),
            'adminUserContainer' => $adminUserArray
        );

        return $this->render('YilinkerBackendBundle:Admin:register.html.twig', $registerData);
    }

    /**
     * Register Account
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAccountAction (Request $request)
    {
        $formData = array(
            'username' => $request->request->get('username', ''),
            'plainPassword' => array (
                'first' => $request->request->get('password', ''),
                'second' => $request->request->get('confirmPassword', '')
            ),
            'firstName' => $request->request->get('firstName'),
            'lastName' => $request->request->get('lastName'),
            'adminRole' => $request->request->get('userRole'),
            '_token' => $request->request->get('csrfToken')
        );
        $form = $this->createForm('admin_account_registration', new AdminUser());
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form->submit($formData);
        $isSuccessful = false;
        $errorMessage= '';
        $adminUserArray = array();

        if ($form->isValid()) {
            $adminUserManager = $this->get('yilinker_backend.admin_user_manager');
            $adminUserEntity = $adminUserManager->addAdminUser($form->getData());
            $isSuccessful = true;

            $adminUserArray = array(
                'id' => $adminUserEntity->getAdminUserId(),
                'username' => $adminUserEntity->getUsername(),
                'firstName' => $adminUserEntity->getFirstName(),
                'lastName' => $adminUserEntity->getLastName(),
                'role' => $adminUserEntity->getAdminRole()->getName(),
                'adminRoleId' => $adminUserEntity->getAdminRole()->getAdminRoleId(),
                'isActive' => $adminUserEntity->getIsActive()
            );

        }
        else {
            $errorMessage = implode($formErrorService->throwInvalidFields($form), ' <br> ');
        }

        $response = array(
            'isSuccessful' => $isSuccessful,
            'data' => $adminUserArray,
            'message' => $errorMessage
        );

        return new JsonResponse($response);
    }

    /**
     * Edit Register Account
     * @param Request $request
     * @return JsonResponse
     */
    public function editAccountAction (Request $request)
    {
        $isSuccessful = false;
        $errorMessage= 'Invalid User';
        $adminUserArray = array();
        $formData = array(
            'firstName' => $request->request->get('firstName'),
            'lastName' => $request->request->get('lastName'),
            'adminRole' => $request->request->get('userRole'),
            '_token' => $request->request->get('csrfToken')
        );
        $em = $this->getDoctrine()->getManager();
        $adminUserEntities = $em->getRepository('YilinkerCoreBundle:AdminUser')->find($request->request->get('userId', 0));

        if ($adminUserEntities) {
            $form = $this->createForm('admin_account_edit', $adminUserEntities);
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $form->submit($formData);
            $errorMessage = '';
            $adminUserArray = array();

            if ($form->isValid()) {
                $adminUserManager = $this->get('yilinker_backend.admin_user_manager');
                $adminUserEntity = $adminUserManager->editAdminUser(
                                                        $adminUserEntities,
                                                        $request->request->get('firstName'),
                                                        $request->request->get('lastName')
                                                    );
                $isSuccessful = true;

                $adminUserArray = array(
                    'id' => $adminUserEntity->getAdminUserId(),
                    'username' => $adminUserEntity->getUsername(),
                    'firstName' => $adminUserEntity->getFirstName(),
                    'lastName' => $adminUserEntity->getLastName(),
                    'role' => $adminUserEntity->getAdminRole()->getName(),
                    'roleId' => $adminUserEntity->getAdminRole()->getAdminRoleId(),
                    'isActive' => $adminUserEntity->getIsActive()
                );

            }
            else {
                $errorMessage = implode($formErrorService->throwInvalidFields($form), ' and ');
            }

            $response = array(
                'isSuccessful' => $isSuccessful,
                'data' => $adminUserArray,
                'message' => $errorMessage
            );
        }
        else {
            $response = array(
                'isSuccessful' => $isSuccessful,
                'data' => $adminUserArray,
                'message' => $errorMessage
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Deactivate Account
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivateAccountAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $adminUserEntities = $em->getRepository('YilinkerCoreBundle:AdminUser')->find($request->request->get('id', 0));
        $isActivate = $request->request->get('isActive');
        $isActivate = trim($isActivate) === 'true';
        $isSuccessful = false;

        if ($adminUserEntities) {
            $adminUserManager = $this->get('yilinker_backend.admin_user_manager');
            $adminUserManager->deactivateAdminUser($adminUserEntities, $isActivate);
            $isSuccessful = true;
        }

        $response = array(
            'isSuccessful' => $isSuccessful,
            'data' => '',
            'message' => ''
        );

        return new JsonResponse($response);
    }

    /**
     * Update the admin password
     *
     */
    public function updateAdminPasswordAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Account not found',
        );

        $em = $this->getDoctrine()->getManager();
        $admin = $em->getRepository('YilinkerCoreBundle:AdminUser')
                    ->find($request->get('adminId', 0));
              
        $form = $this->createForm('admin_password_change');
        $form->submit(array(
            'plainPassword' => array(
                'first'  => $request->get('password'),
                'second' => $request->get('confirmPassword'),
            ),
            '_token' => $request->get('_token'),
        ));

        if ($form->isValid()) {
            $validatedData = $form->getData();
            $admin->setPlainPassword($validatedData['plainPassword']);
            $em->flush();
            $response['message'] = 'Password successfully updated';
            $response['isSuccessful'] = true;
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

}
