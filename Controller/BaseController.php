<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 15.07.14
 * Time: 10:38
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\Controller;

use Application\FOS\UserBundle\Model\UserManager;
use Brother\CommonBundle\AppDebug;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class BaseController
 * @package Brother\CommonBundle\Controller
 */
abstract class BaseController extends AbstractController
{

    /**
     * Возврат аякса
     * @param $result
     * @return Response
     */
    protected function ajaxResponse($result)
    {
        $r = json_encode($result);
        if (json_last_error() != 0) {
            AppDebug::_dx($result, json_last_error_msg());
        }
        $response = new Response();
        $response->setContent($r);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Получение ИД пользователя
     * @return int
     */
    protected function getUserId()
    {
        $user = $this->getUser();
        /* @var $user \Application\Sonata\UserBundle\Entity\User */
        return $user ? $user->getId() : 0;
    }

    /**
     * @param $role
     * @return boolean
     */
    protected function isGranted($attributes, $object = null)
    {
        return parent::isGranted($attributes, $object) && $this->container->get('security.context')->isGranted($attributes);
    }

    /**
     * @param string $action
     * @param $value
     * @param string $value
     */
    protected function setFlash($action, $value)
    {
        $this->container->get('session')->getFlashBag()->add($action, $value);
    }

    /**
     * @param $form AbstractType
     * @return array
     */
    protected function getFormErrors($form)
    {
        $errors = array();
        foreach ($form as $name => $field) {
            /* @var $field \Symfony\Component\Form\Form */
            foreach ($field->getErrors() as $error) {
                /* @var $error \Symfony\Component\Form\FormError */
                $errors[$form->getName() . '_' . $name][] = $error->getMessage();
            }
        }
        return $errors;
    }

}