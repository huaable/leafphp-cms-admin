<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\UploadManager;
use Leaf\Application;
use Leaf\DB;
use Leaf\Request;
use Leaf\Session;
use Leaf\Validator;
use Leaf\View;
use Leaf\Redirect;

/**
 * 文件资源管理管理
 * @author  curd generator
 * @since   1.0
 */
class UploadManagerController
{
    /**
     * 列表
     * @Route admin/upload-manager
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();

        $condition[] = 'status = :status';
        $params[':status'] = UploadManager::STATUS_NO;

        $search = $request->get('UploadManager');

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
        //        if (!empty($value) && in_array($key, array_keys(UploadManager::labels()))) {
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
        $dataProvider = UploadManager::where(implode(' and ', $condition), $params)
            ->orderBy($sortBy)
            ->paginate();

        $entity = new UploadManager();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/upload-manager/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
        ]);
    }

    //    /**
    //     * 新增
    //     * @Route admin/upload-manager/create
    //     */
    //    public function create(Request $request)
    //    {
    //        $entity = new UploadManager();
    //        $entity->loadDefaultValues();
    //
    //        //保存
    //        $error = '';
    //        if ($request->isMethod('POST')) {
    //
    //            $data = $request->get('UploadManager');
    //
    //            if (Validator::validate($data, self::getRules('create'), UploadManager::labels())) {
    //
    //                $data['created_at'] = $data['updated_at'] = Carbon::now();
    //
    //                if (UploadManager::insert($data)) {
    //                    Session::setFlash('message', '添加成功');
    //                    return Redirect::to('admin/upload-manager');
    //                } else {
    //                    $error = '系统错误';
    //                }
    //            } else {
    //                $error = Validator::getFirstError();
    //            }
    //        }
    //
    //        //视图
    //        return View::render('@AdminBundle/upload-manager/create.twig', [
    //            'entity' => $entity,
    //            'error' => $error,
    //        ]);
    //    }

    //    /**
    //     * 更新
    //     * @Route admin/upload-manager/update
    //     */
    //    public function update(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = UploadManager::findByPkOrFail($request->get('id'));
    //
    //        //保存
    //        $error = '';
    //        if ($request->isMethod('POST')) {
    //
    //            $data = $request->get('UploadManager');
    //
    //            //验证
    //            if (Validator::validate($data, self::getRules('update'))) {
    //
    //                $data['updated_at'] = Carbon::now();
    //
    //                //更新
    //                if (UploadManager::wherePk($entity->id)->update($data)) {
    //                    Session::setFlash('message', '修改成功');
    //                    return Redirect::to($request->get('returnUrl', 'admin/upload-manager'));
    //                } else {
    //                    $error = '系统错误';
    //                }
    //
    //            } else {
    //                $error = Validator::getFirstError();
    //            }
    //        }
    //
    //        //视图
    //        return View::render('@AdminBundle/upload-manager/update.twig', [
    //            'entity' => $entity,
    //            'error' => $error,
    //        ]);
    //    }

    /**
     * 删除
     * @Route admin/upload-manager/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        // 删除硬盘上的文件
        $entity = UploadManager::findByPkOrFail($request->get('id'));

        $result = $entity->deleteFile();
        if ($result) {
            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    /**
     * 一键清理删除
     * @Route admin/upload-manager/delete-all
     * @Method post
     */
    public function deleteAll(Request $request)
    {


        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();

        $list = UploadManager::findAll(implode(' and ', $condition), $params);
        foreach ($list as  $entity) {
            if ($entity->status == UploadManager::STATUS_NO) {
                $entity->deleteFile();
            }
        }

        Session::setFlash('message', '清理完毕');

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/upload-manager/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = UploadManager::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/upload-manager/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }

    //    /**
    //     * 验证规则
    //     * @param string $scene create|update
    //     * @return array
    //     */
    //    protected function getRules($scene)
    //    {
    //        $rules = [
    //            [['model', 'model_id', 'filename', 'status',], 'safe'],
    //        ];
    //
    //        return $rules;
    //    }

}
