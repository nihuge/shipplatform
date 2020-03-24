<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 资讯Model
 * */
class ArticleModel extends BaseModel
{
	// 自动验证
    protected $_validate=array(
        array('title','require','文章标题必填'),
        array('author','require','作者必填'),
        array('content','require','文章内容必填'),
        );
    // 添加数据
    public function addData(){
        // 获取post数据
        $data=I('post.');$data['addtime'] = time();
        // 反转义为下文的 preg_replace使用
        $data['content']=htmlspecialchars_decode($data['content']);
        // 判断是否修改文章中图片的默认的alt 和title
        $image_title_alt_word=C('IMAGE_TITLE_ALT_WORD');
        if(!empty($image_title_alt_word)){
            // 修改图片默认的title和alt
            $data['content']=preg_replace('/title=\"(?<=").*?(?=")\"/','title="计量平台"',$data['content']);
            $data['content']=preg_replace('/alt=\"(?<=").*?(?=")\"/','alt="计量平台"',$data['content']);
        }
        // 将绝对路径转换为相对路径
        $data['content']=preg_replace('/src=\"^\/.*\/Upload\/image\/ueditor$/','src="/Upload/image/ueditor',$data['content']);
        // 转义
        $data['content']=htmlspecialchars($data['content']);
        if($this->create($data)){
            //获取文章内容图片
            $image_path=get_ueditor_image_path($data['content']);
            if($aid=$this->add()){
            	if(empty($image_path)){
                    $image_path = array('/tpl/default/Index/Public/image/nothing_photo.png');
                }
                // 传递图片插入数据库
                D('ArticlePic')->addData($aid,$image_path);
                return $aid;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    // 修改数据
    public function editData(){
        // 获取post数据
        $data=I('post.');$data['addtime'] = time();
        // 反转义为下文的 preg_replace使用
        $data['content']=htmlspecialchars_decode($data['content']);
        // 判断是否修改文章中图片的默认的alt 和title
        $image_title_alt_word=C('IMAGE_TITLE_ALT_WORD');
        if(!empty($image_title_alt_word)){
            // 修改图片默认的title和alt
            $data['content']=preg_replace('/title=\"(?<=").*?(?=")\"/','title="计量平台"',$data['content']);
            $data['content']=preg_replace('/alt=\"(?<=").*?(?=")\"/','alt="计量平台"',$data['content']);
        }
        // 将绝对路径转换为相对路径
        $data['content']=preg_replace('/src=\"^\/.*\/Upload\/image\/ueditor$/','src="/Upload/image/ueditor',$data['content']);
        $data['content']=htmlspecialchars($data['content']);
        if($this->create($data)){
            $aid=$data['aid'];
            $this->where(array('aid'=>$aid))->save();
            $image_path=get_ueditor_image_path($data['content']);
            // 删除图片路径
            D('ArticlePic')->deleteData($aid);
            if(empty($image_path)){
                $image_path = array('/tpl/default/Index/Public/image/nothing_photo.png');
            }
            // 传递图片插入数据库
            D('ArticlePic')->addData($aid,$image_path);
            return true;
        }else{
            return false;
        }
    }

    // 彻底删除
    public function deleteData(){
        $aid=I('post.aid',0,'intval');
        D('ArticlePic')->deleteData($aid);
        $this->where(array('aid'=>$aid))->delete();
        return true;
    }

    /**
     * 获得文章分页数据
     * @param strind $is_show   是否显示 1为显示 0为显示
     * @param strind $limit 分页条数
     * @param strind $p 分页数
     * @return array $data 分页样式 和 分页数据
     */
    public function getPageData($is_show='1',$p=1,$limit=30){
        // 获取全部分类、全部标签下的文章
        if($is_show=='all'){
            $where=array('1');
        }else{
            $where=array(
                'is_show'=>$is_show
                );
        }
        $count=$this
            ->where($where)
            ->count();
        //分页
	    $page = fenye($count,$limit);
	    $begin=($p-1)*$limit;

        $list=$this
            ->where($where)
            ->order('addtime desc')
		    ->limit($begin,$limit)
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['addtime']=word_time($v['addtime']);
            $list[$k]['pic_path']=D('ArticlePic')->getDataByAid($v['aid']);
            $v['content']=preg_ueditor_image_path($v['content']);
            $list[$k]['content']=htmlspecialchars($v['content']);
            $list[$k]['url']=U('Article/msg',array('aid'=>$v['aid']));
        }
        $data=array(
            'page'=>$page,
            'data'=>$list,
            );
        return $data;
    }

    // 传递aid获取单条全部数据 $map 主要为前台页面上下页使用
    public function getDataByAid($aid,$map=''){
        if($map==''){
            // $map 为空则不获取上下篇文章
            $data=$this->where(array('aid'=>$aid))->find();
            // 获取相对路径的图片地址
            $data['content']=preg_ueditor_image_path($data['content']);
        }else{

            $prev_map[]=array('is_show'=>1);
            $next_map=$prev_map;
            $prev_map['aid']=array('gt',$aid);
            $next_map['aid']=array('lt',$aid);
            $data['prev']=$this->field('aid,title')->where($prev_map)->limit(1)->find();
            $data['next']=$this->field('aid,title')->where($next_map)->order('aid desc')->limit(1)->find();

            // 如果不为空 添加url
            if(!empty($data['prev'])){
                $data['prev']['url']=U('Article/msg/',array('aid'=>$data['prev']['aid']));
            }
            if(!empty($data['next'])){
                $data['next']['url']=U('Article/msg/',array('aid'=>$data['next']['aid']));
            }
            $data['current']=$this->where(array('aid'=>$aid))->find();
            $data['current']['content']=preg_ueditor_image_path($data['current']['content']);
        }
        return $data;
    }
}