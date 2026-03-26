<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminUserRepository;
use App\Repositories\Backend\ConfigRepository;
use App\Repositories\Backend\ProductRepository;

/**
 * 金币套餐
 *
 * @package App\Controller\Backend
 *
 * @property  ProductRepository $productRepository
 * @property ConfigRepository $configRepo
 * @property AdminUserRepository $adminUserRepo
 */
class ProductController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/product');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->productRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typeItems',CommonValues::getProductType());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->productRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('typeItems',CommonValues::getProductType());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $token=$this->getToken();
        $keyName="google_check_product_{$token['user_id']}";
        if (!getCache($keyName)) {
            $googleCode = $this->getRequest('google_code');
            if (empty($googleCode) || !$this->adminUserRepo->verifyGoogleCode($googleCode)) {
                $this->sendErrorResult("谷歌验证码错误!");
            }
            setCache($keyName, 1, 300);
        }
        $result = $this->productRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 批量操作
     */
    public  function  doAction()
    {
        $idStr = $this->getRequest("id");
        $act = $this->getRequest("act");
        if(empty($idStr) || empty($act)){
            $this->sendErrorResult("操作错误!");
        }
        if($act=='del') {
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $this->productRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}