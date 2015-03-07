<?php

namespace Crud\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class AbstractCrudController extends AbstractActionController
{
    protected $entityName;
    protected $controllerRoute;

    public function indexAction()
    {
        $crudService = $this->getCrudService();
        $items = $crudService->getList($this->entityName);

        return new ViewModel(['items' => $items]);
    }

    public function editAction()
    {
        $id = $this->params()->fromRoute('id');

        $crudService = $this->getCrudService();

        if ($this->getRequest()->isPost()) {
            $fromPost = array_merge_recursive($this->params()->fromPost(), $this->params()->fromFiles());
            $form = $crudService->edit($this->entityName, $id, $fromPost);
            if ($form === true) {
                return $this->redirect()->toRoute('crud', ['controller' => $this->controllerRoute, 'action' => 'index']);
            }
        } else {
            $form = $crudService->edit($this->entityName, $id);
        }

        $view = new ViewModel(['form' => $form]);
        $view->setTemplate('crud/form');
        return $view;
    }

    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');

        $crudService = $this->getCrudService();
        $crudService->delete($this->entityName, $id);

        return $this->redirect()->toRoute('crud', ['controller' => $this->controllerRoute, 'action' => 'index']);
    }

    /**
     * @return array|object
     */
    protected function getCrudService()
    {
        $crudService = $this->getServiceLocator()->get('Crud\Service\Crud');
        return $crudService;
    }
}