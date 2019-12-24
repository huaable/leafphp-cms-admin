<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
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
 * 语言站点管理
 * @author  curd generator
 * @since   1.0
 */
class LangController
{
    use UploaderTrait;

    /**
     * 列表
     * @Route admin/lang
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];
        $search = $request->get('Lang');


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
        //        if (!empty($value) && in_array($key, array_keys(Lang::labels()))) {
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
        $dataProvider = Lang::where(implode(' and ', $condition), $params)
            ->orderBy($sortBy)
            ->paginate();

        $entity = new Lang();
        $entity->loadDefaultValues();

        $lang = Lang::getLang();

        //视图
        return View::render('@AdminBundle/lang/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
            'lang' => $lang,
        ]);
    }

    /**
     * 新增
     * @Route admin/lang/create
     */
    public function create(Request $request)
    {
        $entity = new Lang();
        $entity->loadDefaultValues();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Lang');

            if (Validator::validate($data, self::getRules('create'), Lang::labels())) {

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, Lang::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['flag'] = $file;
                }

                $data['created_at'] = $data['updated_at'] = Carbon::now();

                if ($insertId = Lang::insertGetId($data)) {

                    //整理资源
                    UploadManager::clear(Lang::findByPk($insertId));

                    Lang::install($data['key']);
                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/lang');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/lang/create.twig', [
            'entity' => $entity,
            'error' => $error,
        ]);
    }

    /**
     * 更新
     * @Route admin/lang/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = Lang::findByPkOrFail($request->get('id'));

        $images = $entity->flag ? [$entity] : [];
        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Lang');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {


                //处理已删除的图片
                $oldImgIds = Util::arrayColumn($images, 'id');
                $keepImageIds = $request->get('imageIds', []);

                $removeIds = array_diff($oldImgIds, $keepImageIds);

                if (!empty($removeIds)) {

                    $data['flag'] = '';
                }

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, Lang::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['flag'] = $file;
                }

                $data['updated_at'] = Carbon::now();

                //更新
                if (Lang::wherePk($entity->id)->update($data)) {


                    //整理资源
                    UploadManager::clear($entity);

                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/lang'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/lang/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => $images,
        ]);
    }

    /**
     * 删除
     * @Route admin/lang/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        $count = Lang::count();

        if ($count == 1) {
            Session::setFlash('message', '删除失败，至少保留一项');
            return Redirect::back();
        }

        //查询
        $entity = Lang::findByPkOrFail($request->get('id'));
        if ($entity->key == Lang::getLang()) {
            //切换其他语言
            $one = Lang::findOne();
            Lang::setLang($one->key);
        }

        $result = Lang::wherePk($request->get('id'))->delete();

        if ($result) {
            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    /**
     * 删除
     * @Route admin/lang/change
     * @Method get
     */
    public function change(Request $request)
    {
        //查询
        $entity = Lang::findByPkOrFail($request->get('id'));
        Lang::setLang($entity->key);
        Session::setFlash('message', '切换成功');

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/lang/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = Lang::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/lang/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }


    /**
     * fileupload插件上传配图
     * @Route admin/lang/upload
     */
    public function upload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('file', [], Lang::className());
        return Json::render($up);
    }

    /**
     * 验证规则
     * @param string $scene create|update
     * @return array
     */
    protected function getRules($scene)
    {

        $rules = [
            [['name', 'key',], 'safe'],
            [['name', 'key',], 'required'],
            ['key', 'unique', 'table' => Lang::tableName(), 'field' => 'key'],
        ];

        if ($scene == 'update') {
            $rules = [
                [['name',], 'safe'],
                [['name',], 'required'],
            ];
        }

        return $rules;
    }

}
