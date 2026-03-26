<?php


namespace App\Core;


abstract class ShouldQueue
{
    private $jobDrive;

    /**
     * 执行
     * @param $uniqid
     * @return mixed
     */
    abstract public function handler($uniqid);

    /**
     * 成功
     * @param $uniqid
     * @return mixed
     */
    abstract public function success($uniqid);

    /**
     * 失败
     * @param $uniqid
     * @return mixed
     */
    abstract public function error($uniqid);

    /**
     * 设置驱动
     * @param $drive
     */
    public function setJobDrive($drive)
    {
        $this->jobDrive=$drive;
    }

    /**
     * 获取驱动
     * @return mixed
     */
    public function getJobDrive()
    {
        return $this->jobDrive;
    }
}