<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\SystemPost;
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
use Service\UploaderTrait;

/**
 * 系统文章管理
 * @author  curd generator
 * @since   1.0
 */
class SystemPostController
{
    use UploaderTrait;

    /**
     * 列表
     * @Route admin/system-post
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];
        $search = $request->get('SystemPost');

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();

        if (!empty($search['id'])) {
            $condition[] = 'id = :id';
            $params[':id'] = $search['id'];
            unset($search['id']);
        }

        //if (!empty($search['name'])) {
        //    $condition[] = 'name like :name';
        //    $params[':name'] = '%' . trim($search['name']) . '%';
        //    unset($search['name']);
        //}

        //if (!empty($search)) {
        //    foreach ($search as $key => $value) {
        //        if (!empty($value) && in_array($key, array_keys(SystemPost::labels()))) {
        //            $condition[] = '`' . $key . '` like :' . $key;
        //            $params[':' . $key] = '%' . trim($search[$key]) . '%';
        //        }
        //    }
        //}

        $sortBy = $request->get('sortby', '-id');
        if (!in_array(ltrim($sortBy, '-'), ['id'])) {
            Session::setFlash('message', '指定字段不支持排序');
            return Redirect::back();
        }

        //数据
        $dataProvider = SystemPost::where(implode(' and ', $condition), $params)
            ->orderBy($sortBy)
            ->paginate();

        $entity = new SystemPost();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/system-post/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/system-post/create
     */
    public function create(Request $request)
    {
        $entity = new SystemPost();
        $entity->loadDefaultValues();
        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('SystemPost');

            if (Validator::validate($data, self::getRules('create'), SystemPost::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, SystemPost::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['filename'] = $file;
                }


                if ($insertId = SystemPost::insertGetId($data)) {

                    //资源管理器:整理资源
                    UploadManager::clear(SystemPost::findByPk($insertId));

                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/system-post');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }


        }


        //视图
        return View::render('@AdminBundle/system-post/create.twig', [
            'entity' => $entity,
            'error' => $error,
        ]);
    }

    /**
     * 更新
     * @Route admin/system-post/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = SystemPost::findByPkOrFail($request->get('id'));
        $images = $entity->filename ? [$entity] : [];

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('SystemPost');

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
                    $file = $this->fileKeyToFilename($imageKey, SystemPost::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['filename'] = $file;
                }


                //更新
                if (SystemPost::wherePk($entity->id)->update($data)) {

                    //资源管理器:整理资源
                    UploadManager::clear($entity);

                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/system-post'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }


        //视图
        return View::render('@AdminBundle/system-post/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => $images,
        ]);
    }

    /**
     * 删除
     * @Route admin/system-post/delete
     * @Method post
     */
    public function delete(Request $request)
    {
        $entity = SystemPost::findByPkOrFail($request->get('id'));
        $result = SystemPost::wherePk($request->get('id'))->delete();

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
    //     * @Route admin/system-post/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = SystemPost::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/system-post/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }

    /**
     * fileupload插件上传配图
     * @Route admin/system-post/upload
     */
    public function upload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('file', [], SystemPost::className());
        return Json::render($up);
    }


    /**
     * 百度富文本编辑器上传图片
     * @Route admin/system-post/um-upload
     */
    public function umUpload(Request $request, Application $app)
    {

        $up = $this->uploadToPath('upfile', [], SystemPost::className());

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


    }


    /**
     * 验证规则
     * @param string $scene create|update
     * @return array
     */
    protected function getRules($scene)
    {
        $rules = [
            [['key', 'title', 'content',], 'safe'],
            [['key',], 'required'],
        ];

        return $rules;
    }

}
