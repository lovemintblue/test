<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Repositories\Backend\AdminUserRepository;

/**
 * Class IndexController
 * @package App\Controller\Backend
 * @property AdminUserRepository $adminUserRepository
 */
class IndexController extends BaseBackendController
{
    public function indexAction()
    {
        $menus = $this->adminUserRepository->getMenus();
        $this->view->setVars(array(
            'menus' => $menus
        ));
    }
}