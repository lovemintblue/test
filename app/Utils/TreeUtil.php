<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Tree类
 * @copyright Copyright (c) 2011 - 2025 xxxxx
 * @author kk
 */
class TreeUtil
{
    
    private  $_data= array();
    
    private  $_idName = 'id';
    
    private  $_valueName = 'name';
    
    private  $_parentIdName = 'parentId';
    
    
    /**
     *
     * @param $data
     * @param string $idName
     * @param string $valueName
     * @param string $parentIdName
     */
    public function __construct( $data, $idName = 'id',$valueName = 'name',$parentIdName = 'parent_id')
    {
        $this->_data = $data;
        $this->_idName = $idName;
        $this->_valueName = $valueName;
        $this->_parentIdName = $parentIdName;
        
    }
    
    /**
     * 獲取所有分類
     */
    public  function getData()
    {
        return $this->_data;
    }


    /**
     * 獲取分類options
     * @param string $id
     * @param string $delimiter
     * @param bool $outId
     * @param null $valueKey
     * @return string
     */
    public  function getHtmlOptions($id = '',$delimiter='--',$outId = false,$valueKey=null)
    {
        
        $categories = $this->unlimitedForLevel($delimiter,$id);
        $htmlOptions = '';
        $currentKey = $this->_idName;
        if(empty($valueKey)){
            $valueKey = $currentKey;
        }
        $parentKey = $this->_parentIdName;
        $valName = $this->_valueName;
        foreach ($categories as $item)
        {
            if($item[$currentKey] == $outId) continue;
            
            if($item[$currentKey] == $id){
                $htmlOptions .= '<option value="'. $item[$valueKey].'" selected data-pid="'.$item[$parentKey].'">'. $item['delimiter']. $item[$valName].'</option>';
            }else{
                $htmlOptions .= '<option value="'. $item[$valueKey].'" data-pid="'.$item[$parentKey].'">'. $item['delimiter']. $item[$valName].'</option>';
            }
        }
        return $htmlOptions;
    }


    /**
     * 一维数组
     * @param string $delimiter
     * @param int $pid
     * @param int $level
     * @return array
     */
    public function unlimitedForLevel ($delimiter = '———', $pid = 0, $level = 0)
    {
        $cate = $this->getData();
        $arr = array();
        $parentkey = $this->_parentIdName;
        $currentkey = $this->_idName;
        foreach ($cate as $v) {
            if ($v[$parentkey] == $pid) {
                $v['level'] = $level + 1;
                $v['delimiter'] = str_repeat('&nbsp;&nbsp;', $level).str_repeat($delimiter, $level);
                $arr[] = $v;
                $arr = array_merge($arr,$this->unlimitedForLevel($delimiter, $v[$currentkey],$v['level']));
            }
        }
        
        return $arr;
    }

    /**
     * 组成多维数组
     * @param string $name
     * @param int $pid
     * @return array
     */
    public function unlimitedForLayer ( $name = 'child', $pid = 0)
    {
    	$cate = $this->getData();
        $arr = array();
        $parentIdName = $this->_parentIdName;
        $idName = $this->_idName;
        foreach ($cate as $v) {
            if ($v[$parentIdName] == $pid) {
                $v[$name] = $this->unlimitedForLayer($name, $v[$idName]);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    /**
     * 获取分类树
     * @param string $name
     * @param int $pid
     * @return array
     */
    public  function getTree($name = 'child', $pid = 0)
    {
        return  $this->unlimitedForLayer($name,$pid);
    }

    /**
     * 传递一个子分类ID返回他的所有父级分类
     *
     * @param  $cate
     * @param  $id
     * @return array
     */
    public function getParents ($cate, $id)
    {
        $arr = array();
        
        if (empty($cate)) {
            return $arr;
        }
        
        foreach ($cate as $v) {
            if ($v[$this->_idName] == $id) {
                $arr[] = $v;
                $arr = array_merge($this->getParents($cate, $v[$this->_parentIdName]), $arr);
            }
        }
        return $arr;
    }
    
    /**
     * 传递一个子分类ID返回他的同级分类
     *
     * @param  $cate
     * @param  $id
     * @return multitype:|multitype:unknown
     */
    public function getSameCate ($cate, $id)
    {
        $arr = array();
        $self = $this->getSelf($cate, $id);
        if (empty($self)) {
            return $arr;
        }
        
        foreach ($cate as $v) {
            if ($v[$this->_idName] == $self[$this->_parentIdName]) {
                $arr[] = $v;
            }
        }
        return $arr;
    }
    
    /**
     * 判断分类是否有子分类,返回false,true
     *
     * @param array $cate
     * @param  $id
     * @return boolean
     */
    public function hasChild ($cate, $id)
    {
        $arr = false;
        foreach ($cate as $v) {
            if ($v[$this->_parentIdName] == $id) {
                $arr = true;
                return $arr;
            }
        }
        
        return $arr;
    }

    /**
     * 传递一个父级分类ID返回所有子分类ID
     * @param $pid
     * @param int $flag
     * @return array
     */
    public function getChildsId ($pid, $flag = 0)
    {
        $cate = $this->getData();
        $arr = array();
        if ($flag) {
            $arr[] = $pid;
        }
        foreach ($cate as $v) {
            if ($v[$this->_parentIdName] == $pid) {
                $arr[] = $v[$this->_idName];
                $arr = array_merge($arr, $this->getChildsId($v[$this->_idName]));
            }
        }
        
        return $arr;
    }
    
    
    
    /**
     * 传递一个父级分类ID返回所有子级分类
     * @param  $pid
     * @return 
     */
    public function getChilds ($pid)
    {
        $cate = $this->getData();
        $arr = array();
        foreach ($cate as $v) {
            if ($v[$this->_parentIdName] == $pid) {
                $arr[] = $v[$this->_idName];
                $arr = array_merge($arr, $this->getChilds($v[$this->_idName]));
            }
        }
        return $arr;
    }
    
    /**
     * 传递一个分类ID返回该分类相当信息
     * @param integer $id
     * @return 
     */
    public function getSelf ($id)
    {
        $cate = $this->getData();
        $arr = array();
        foreach ($cate as $v) {
            if ($v[$this->_idName] == $id) {
                $arr = $v;
                return $arr;
            }
        }
        return $arr;
    }
}
