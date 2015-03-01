<?php

namespace Crud\Service;

use Owned\Model\OwnedInterface;
use Owned\Model\Repository\FindByOwnerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Exception\UnauthorizedException;

class Crud implements FactoryInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \DoctrineORMModule\Form\Annotation\AnnotationBuilder
     */
    protected $formBuilder;

    /**
     * @var \ZfcRbac\Service\AuthorizationService
     */
    protected $authenticationService;

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this->setEntityManager($serviceLocator->get('Doctrine\ORM\EntityManager'));
        $this->setFormBuilder($serviceLocator->get('FormBuilder'));
        $this->setAuthenticationService($serviceLocator->get('ZfcRbac\Service\AuthorizationService'));

        return $this;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @return $this
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param \DoctrineORMModule\Form\Annotation\AnnotationBuilder $formBuilder
     * @return $this
     */
    public function setFormBuilder($formBuilder)
    {
        $this->formBuilder = $formBuilder;
        return $this;
    }

    /**
     * @return \DoctrineORMModule\Form\Annotation\AnnotationBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * @param \ZfcRbac\Service\AuthorizationService $authenticationService
     * @return $this
     */
    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
        return $this;
    }

    /**
     * @return \ZfcRbac\Service\AuthorizationService
     */
    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    public function getList($entityName)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository($entityName);
        if ($repository instanceof FindByOwnerInterface) {
            return $repository->findAllByOwner($this->getAuthenticationService()->getIdentity());
        }

        return $repository->findAll();
    }

    public function edit($entityName, $id = false, $postData = null)
    {
        $em = $this->getEntityManager();
        if ($id) {
            $entity = $em->find($entityName, $id);

            if (is_null($entity) || !$this->getAuthenticationService()->isGranted($entityName . '#Edit', $entity)) {
                throw new UnauthorizedException('You do not have access to edit this');
            }

        } else {
            $entity = $this->createNewEntity($entityName);
            if ($entity instanceof OwnedInterface) {
                $entity->setOwner($this->getAuthenticationService()->getIdentity());
            }
        }

        /** @var \Zend\Form\Form $form */
        $form = $this->getFormBuilder()->createForm($entity);
        $form->bind($entity);

        if (!is_null($postData)) {
            $form->setData($postData);
            if ($form->isValid()) {
                $em->persist($entity);
                $em->flush();
                return true;
            }
        }

        $form->add(['type'=>'Button', 'name'=>'submit','options'=>['label'=>'Save'], 'attributes'=>['type'=>'submit']]);

        return $form;
    }

    public function delete($entityName, $id)
    {
        $em = $this->getEntityManager();
        if ($id) {
            $entity = $em->find($entityName, $id);

            if (is_null($entity) || !$this->getAuthenticationService()->isGranted($entityName . '#Delete', $entity)) {
                return false;
            }

            $em->remove($entity);
            $em->flush();
            return true;
        }

        return false;
    }

    /**
     * @param $entityName
     * @return mixed
     */
    protected function createNewEntity($entityName)
    {
        $entity = new $entityName;
        return $entity;
    }
}