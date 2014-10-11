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
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Response;

use Brother\CommonBundle\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter;
use Brother\CommonBundle\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy;
use Brother\CommonBundle\Twig\CacheExtension\Extension as CacheExtension;


abstract class BaseController extends Controller
{

    public function initCache()
    {
        $m = $this->get('doctrine_mongodb')->getManager();
        /* @var $m DocumentManager */
        $cache = $m->getConfiguration()->getMetadataCacheImpl();
        $cacheProvider = new DoctrineCacheAdapter($cache);
        $lifetimeCacheStrategy = new LifetimeCacheStrategy($cacheProvider);
        $cacheExtension = new CacheExtension($lifetimeCacheStrategy);
        $twig = $this->get('twig');
        $twig->addExtension($cacheExtension);
    }


    /**
     * Возврат аякса
     * @param $result
     * @return Response
     */
    protected function ajaxResponse($result)
    {
        $response = new Response();
        $response->setContent(json_encode($result));
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
    protected function isGranted($role)
    {
        return $this->container->get('security.context')->isGranted($role);
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