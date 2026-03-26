<?php


namespace App\Controller\Backend;


use App\Controller\BaseBackendController;
use App\Repositories\Backend\AnalysisRepository;

/**
 * Class AnalysisController
 * @property AnalysisRepository $analysisRepository
 * @package App\Controller\Backend
 */
class AnalysisController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 列表
     */
    public function movieAction()
    {
        $this->checkPermission('/analysisMovie');
        if($this->isPost()) {
            $result = $this->analysisRepository->getMovieList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }
    /**
     * 列表
     */
    public function cartoonAction()
    {
        $this->checkPermission('/analysisCartoon');
        if($this->isPost()) {
            $result = $this->analysisRepository->getCartoonList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

}