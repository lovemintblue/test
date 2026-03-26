<?php


namespace App\Controller\Backend;


use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Repositories\Backend\AccountRepository;

/**
 * Class AccountLogsController
 * @property AccountRepository $accountRepository
 * @package App\Controller\Backend
 */
class AccountController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/account');
    }


    /**
     * 账号余额日志
     */
    public function listAction()
    {
        if ($this->isPost()){
            $result = $this->accountRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('logArr',CommonValues::getAccountLogsType());
        $this->view->setVar('recordArr',CommonValues::getAccountRecordType());
    }

    /**
     * 积分余额日志
     */
    public function creditAction()
    {
        if ($this->isPost()){
            $result = $this->accountRepository->getCreditList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typeArr',CommonValues::getCreditType());
    }

}