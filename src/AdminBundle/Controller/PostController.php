<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\Post;
use Entity\PostCategory;
use Entity\Setting;
use Entity\UploadManager;
use Leaf\Application;
use Leaf\DB;
use Leaf\Json;
use Leaf\Request;
use Leaf\Session;
use Leaf\Util;
use Leaf\Validator;
use Leaf\View;
use Leaf\Redirect;
use Service\UploadManagerTrait;
use Service\UploaderTrait;

/**
 * 文章管理
 * @author  curd generator
 * @since   1.0
 */
class PostController
{

    use  UploaderTrait;

    /**
     * 列表
     * @Route admin/post
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];
        $search = $request->get('Post');

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();


        if (!empty($search['name'])) {
            $condition[] = 'name like :name';
            $params[':name'] = '%' . trim($search['name']) . '%';
            unset($search['name']);
        }

        if (!empty($search)) {
            foreach ($search as $key => $value) {
                if (!empty($value) && in_array($key, array_keys(Post::labels()))) {
                    $condition[] = '`' . $key . '` like :' . $key;
                    $params[':' . $key] = '%' . trim($search[$key]) . '%';
                }
            }
        }

        //        $sortBy = $request->get('sortby', '-recommend');
        //        if (!in_array(ltrim($sortBy, '-'), ['recommend','id'])) {
        //            Session::setFlash('message', '指定字段不支持排序');
        //            return Redirect::back();
        //        }

        //数据
        $dataProvider = Post::where(implode(' and ', $condition), $params)
            //            ->orderBy($sortBy)
            ->orderBy('-recommend,-id')
            ->paginate();

        $entity = new Post();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/post/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
            'categoryList' => PostCategory::getAllChild()
        ]);
    }


    /**
     * 新增
     * @Route admin/post/create
     */
    public function create(Request $request)
    {
        $entity = new Post();
        $entity->loadDefaultValues();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Post');

            if (Validator::validate($data, self::getRules('create'), Post::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, Post::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['filename'] = $file;
                }

                if ($insertId = Post::insertGetId($data)) {

                    //资源管理器:整理图片资源
                    UploadManager::clear(Post::findByPk($insertId) );
                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/post');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/post/create.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => PostCategory::getAllChild()
        ]);
    }

    /**
     * 更新
     * @Route admin/post/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = Post::findByPkOrFail($request->get('id'));
        $images = $entity->filename ? [$entity] : [];

        $categoryList = PostCategory::getAllChild();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Post');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {
                $data['updated_at'] = Carbon::now();


                //处理已删除的图片
                $oldImgIds = Util::arrayColumn($images, 'id');
                $keepImageIds = $request->get('imageIds', []);

                $removeIds = array_diff($oldImgIds, $keepImageIds);

                if (!empty($removeIds)) {

                    $data['filename'] = '';
                }

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, Post::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['filename'] = $file;
                }

                //更新
                if (Post::wherePk($entity->id)->update($data)) {

                    //资源管理器:整理资源
                    UploadManager::clear($entity);

                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/post'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/post/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => $images,
            'categoryList' => $categoryList,
        ]);
    }


    /**
     * 删除
     * @Route admin/post/delete
     * @Method post
     */
    public function delete(Request $request)
    {


        $entity = Post::findByPkOrFail($request->get('id'));
        $result = Post::wherePk($request->get('id'))->delete();

        if ($result) {
            UploadManager::clear($entity);

            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/post/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = Post::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/post/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }


    /**
     * fileupload插件上传配图
     * @Route admin/post/upload
     */
    public function upload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('file', [], Post::className());
        return Json::render($up);
    }

    /**
     * 百度富文本编辑器上传图片
     * @Route admin/post/um-upload
     */
    public function umUpload(Request $request, Application $app)
    {

        $up = $this->uploadToPath('upfile', [], Post::className());

        if ($up['status']) {

            $info = [
                "url" => $up['file']['url'],
                "state" => 'SUCCESS',
            ];

        } else {

            $info = [
                "state" => $up->message
            ];

        }

        $callback = $request->get('callback');
        if ($callback) {
            return '<script>' . $callback . '(' . json_encode($info) . ')</script>';
        } else {
            return json_encode($info);
        }


        //        "SUCCESS" ,                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        //        "文件大小超出 upload_max_filesize 限制" ,
        //        "文件大小超出 MAX_FILE_SIZE 限制" ,
        //        "文件未被完整上传" ,
        //        "没有文件被上传" ,
        //        "上传文件为空" ,
        //        "POST" => "文件大小超出 post_max_size 限制" ,
        //        "SIZE" => "文件大小超出网站限制" ,
        //        "TYPE" => "不允许的文件类型" ,
        //        "DIR" => "目录创建失败" ,
        //        "IO" => "输入输出错误" ,
        //        "UNKNOWN" => "未知错误" ,
        //        "MOVE" => "文件保存时出错",
        //        "DIR_ERROR" => "创建目录失败"


    }


    /**
     * 验证规则
     * @param string $scene create|update
     * @return array
     */
    protected function getRules($scene)
    {
        $rules = [
            [['category_id', 'status', 'recommend', 'author', 'title', 'summary', 'content',], 'safe'],
            [['category_id', 'status', 'recommend',], 'integer'],
            [['category_id',], 'required'],
        ];

        return $rules;
    }


}
