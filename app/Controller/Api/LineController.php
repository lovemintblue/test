<?php


namespace App\Controller\Api;

use App\Core\Controller\BaseController;
use App\Services\DomainService;

/**
 * Class LineController
 * @property DomainService $domainService
 * @package App\Controller\Api
 */
class LineController extends BaseController
{
    /**
     * 检查线路
     */
    public function indexAction()
    {
        $domains = $this->domainService->getAllGroupBy();
        $domains = $domains['h5'];
        $id = empty($_REQUEST['id'])?0:intval($_REQUEST['id']);
        $id = $id>=count($domains)?0:$id;
        $data = array(
            'url' => strval($domains[$id])
        );
        if($_REQUEST['callback']){
            ob_clean();
            echo $_REQUEST['callback'].'('.json_encode($data).')';
            exit;
        }else{
            $this->sendJson($data);
        }
    }

    /**
     * ping
     */
    public function pingAction()
    {
        $data = array(
            'status' =>'y'
        );
        if($_REQUEST['callback']){
            ob_clean();
            echo $_REQUEST['callback'].'('.json_encode($data).')';
            exit;
        }else{
            $this->sendJson($data);
        }
    }
}