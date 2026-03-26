<?php


namespace App\Tasks;


use App\Constants\CommonValues;
use App\Core\BaseTask;
use App\Jobs\Common\MysqlJob;
use App\Jobs\Common\AsyncAdvJob;
use App\Jobs\Model\MovieFactor;
use App\Models\BookModel;
use App\Models\ComicsChapterModel;
use App\Models\ComicsTagModel;
use App\Models\MovieAlbumModel;
use App\Models\MovieModel;
use App\Models\MovieTagModel;
use App\Models\UserModel;
use App\Models\UserPhotoModel;
use App\Models\WithdrawModel;
use App\Repositories\Api\CommentRepository;
use App\Services\AccountService;
use App\Services\AgentSystemService;
use App\Services\ApiService;
use App\Services\BookService;
use App\Services\CartoonChapterCopyService;
use App\Services\CartoonChapterService;
use App\Services\CartoonCopyService;
use App\Services\CartoonFavoriteService;
use App\Services\CartoonService;
use App\Services\CdnService;
use App\Services\ChatService;
use App\Services\ComicsFavoriteService;
use App\Services\ComicsService;
use App\Services\ComicsTagService;
use App\Services\CommonService;
use App\Services\CreditLogService;
use App\Services\ElasticService;
use App\Services\JobService;
use App\Services\M3u8Service;
use App\Services\MmsService;
use App\Services\MovieAlbumService;
use App\Services\MovieBlockService;
use App\Services\MovieCopyService;
use App\Services\MovieDownloadService;
use App\Services\MovieFavoriteService;
use App\Services\MovieLoveService;
use App\Services\MovieService;
use App\Services\MovieTagService;
use App\Services\NovelFavoriteService;
use App\Services\PaymentService;
use App\Services\PlayService;
use App\Services\PostService;
use App\Services\RechargeService;
use App\Services\ReportAdvLogService;
use App\Services\TokenService;
use App\Services\UserActorService;
use App\Services\UserAgentService;
use App\Services\UserMessageService;
use App\Services\UserOrderService;
use App\Services\UserPhotoService;
use App\Services\UserService;
use App\Services\UserCodeService;
use App\Services\UserCouponService;
use App\Services\UserSignService;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\HanziConvert;
use App\Utils\LogUtil;
use Phalcon\Cli\Task;

/**
 * Class TestTask
 * @package App\Tasks
 * @property CommonService $commonService
 * @property TokenService $tokenService
 * @property MovieService $movieService
 * @property UserService $userService
 * @property UserOrderService $userOrderService
 * @property RechargeService $rechargeService
 * @property PlayService $playService
 * @property UserCodeService $userCodeService
 * @property UserCouponService $userCouponService
 * @property MovieFavoriteService $movieFavoriteService
 * @property ComicsFavoriteService $comicsFavoriteService
 * @property ElasticService $elasticService
 * @property PaymentService $paymentService
 * @property MmsService $mmsService
 * @property CreditLogService $creditLogService
 * @property ApiService $apiService
 * @property MovieDownloadService $movieDownloadService
 * @property JobService $jobService
 * @property M3u8Service $m3u8Service
 * @property CdnService $cdnService
 * @property ComicsService $comicsService
 * @property ChatService $chatService
 * @property PostService $postService
 * @property ComicsChapterModel $comicsChapterModel
 * @property ComicsTagService $comicsTagService
 * @property MovieTagService $movieTagService
 * @property MovieLoveService $movieLoveService
 * @property NovelFavoriteService $novelFavoriteService
 * @property AgentSystemService $agentSystemService
 */
class TestTask extends BaseTask
{
   public function aAction()
   {
       $ip = '2408:8435:2160:1b29:7043:7dff:fee4:5503';
       $result = $this->agentSystemService->getCodeByIp(['ip'=>$ip]);
       $page=1;
       $pageSize=1000;
       while (true){
           $rows = $this->movieService->getList([],[],[],($page-1)*$pageSize,$pageSize);
           if(empty($rows)){
               break;
           }
           foreach ($rows as &$row) {
               $row=[
                   'mid'=>$row['mid'],
                   'name'=>$row['name'],
                   'img'=>$row['img_x'],
                   'm3u8_url'=>$row['m3u8_url'],
                   'preview_m3u8_url'=>$row['preview_m3u8_url'],
                   'tags'=>value(function ()use($row){
                       $tags = array();
                       foreach ($row['tags'] as $tagId)
                       {
                           $tag = $this->movieTagService->findByID(intval($tagId));
                           $tags[]=[
                               'id'=>$tag['_id'],
                               'name'=>$tag['name'],
                               'group'=>$tag['attribute']
                           ];
                       }
                       return $tags;
                   }),
                   'cat_id'=>$row['cat_id']??"GC",
                   'preview_images'=>$row['preview_images'],
                   'description'=>$row['description'],
                   'categories'=>$row['cat_id']??"GC",
                   'duration'=>$row['duration'],

                   'img_type'=>$row['img_type'],
                   'is_more_link'=>$row['is_more_link'],
                   'update_status'=>$row['update_status'],
                   'canvas'=>$row['canvas'],
                   'width'=>$row['width'],
                   'height'=>$row['height'],


                   'img_width'=>$row['img_width'],
                   'img_height'=>$row['img_height'],
                   'issue_date'=>$row['issue_date'],
               ];
               unset($row);
           }
           file_put_contents(WEB_PATH."/movie/movie-{$page}.json",json_encode($rows,JSON_UNESCAPED_UNICODE));
           $page++;
       }

   }

    public function bAction()
    {
        $txt="亲。请您认真阅读文字内容提供以下全部资料：(1.原账号凭证 2.原邀请码 3.绑定的手机号 4.购买的VIP等级 5.支付账单) 注：如以上资料无法提供，这边将无法为您找回会员，您可重新开通vip，并且及时保存全部的凭证，方便以后找回会员账号哦·，谢谢您的理解和配合！
如上述资料已准备好，请您把全部资料直接发给app在线客服，待客服查询核实后即可处理！";
        $page=1;
        $pageSize=10000;
        while (true){
            $rows = $this->chatService->getList(['user_id'=>-1,'status'=>0],[],[],($page-1)*$pageSize,$pageSize);
            if(empty($rows)){
                break;
            }
            foreach ($rows as $row) {
                $this->chatService->send(-1,$row['to_user_id'],'text',$txt);
                LogUtil::info("id:{$row['_id']}");
            }
        }
    }

    /**
     * 同步广告-临时
     */
    public function testAction($url='')
    {
        $page=1;
        $pageSize=1000;
        while (true){
            $rows = $this->userActService->getList(['is_valid'=>null],[],[],($page-1)*$pageSize,$pageSize);
            if(empty($rows)){
                break;
            }
            $userActKeys = array_keys(CommonValues::getUserActs());
            foreach ($rows as $row) {
                $isValid = false;
                $mustActNum = 0;
                $otherActNum = 0;
                foreach($userActKeys as $userActKey){
                    $act[$userActKey] = intval($row['act']->$userActKey);
                    if(empty($act[$userActKey])){

                    }elseif(in_array($userActKey,['enter_app','close_ad','close_appstore','close_notice'])){
                        ++$mustActNum;
                    }else{
                        ++$otherActNum;
                    }
                }

                //必点行为种类>=2种,其他行为种类>=2种
                if($mustActNum>=2&&$otherActNum>=2){
                    $isValid = true;
                }

                $this->userActService->userActModel->updateRaw(['$set'=>[
                    'is_valid'=>intval($isValid)
                ]],['_id'=>($row['_id'])]);
                LogUtil::info("user_act id:{$row['_id']}");
            }
        }
    }

    public function cAction()
    {
//        $a = $this->movieService->asyncMrs(array('id'=>'ef9db1004738fb5e6547ab4b9c74d6f6','source'=>'laosiji'));

//        $userId = 1048648;
//        $movieIds = $this->movieFavoriteService->getList(['user_id'=>1048648],['movie_id'],['created_at'=>-1],0,500);
//        dd($movieIds);
//        dd(array_column($movieIds,'movie_id'));
        $a = $this->novelFavoriteService->has(680914,'5b2d8e1eea827028');
        dd($a);
    }
}