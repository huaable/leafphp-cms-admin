<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\Setting;
use Leaf\Application;
use Leaf\DB;
use Leaf\Request;
use Leaf\Session;
use Leaf\Validator;
use Leaf\View;
use Leaf\Redirect;

/**
 * 管理
 * @author  curd generator
 * @since   1.0
 */
class SettingController
{
    /**
     * 列表
     * @Route admin/setting
     */
    public function index(Request $request)
    {

        //查询条件
        $condition = [];
        $params = [];
        $search = $request->get('Setting');

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

        if (!empty($search)) {
            foreach ($search as $key => $value) {
                if (!empty($value) && in_array($key, array_keys(Setting::labels()))) {
                    $condition[] = '`' . $key . '` like :' . $key;
                    $params[':' . $key] = '%' . trim($search[$key]) . '%';
                }
            }
        }

        $sortBy = $request->get('sortby', 'id');
        if (!in_array(ltrim($sortBy, '-'), ['id'])) {
            Session::setFlash('message', '指定字段不支持排序');
            return Redirect::back();
        }

        //数据
        $dataProvider = Setting::where(implode(' and ', $condition), $params)
            ->orderBy($sortBy)
            ->paginate();

        $entity = new Setting();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/setting/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/setting/create
     */
    public function create(Request $request)
    {
        $entity = new Setting();
        $entity->loadDefaultValues();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Setting');

            if (Validator::validate($data, self::getRules('create'), Setting::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                if (Setting::insert($data)) {
                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/setting');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/setting/create.twig', [
            'entity' => $entity,
            'error' => $error,
        ]);
    }

    /**
     * 更新
     * @Route admin/setting/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = Setting::findByPkOrFail($request->get('id'));

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Setting');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {

                $data['updated_at'] = Carbon::now();

                //更新
                if (Setting::wherePk($entity->id)->update($data)) {
                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/setting'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/setting/update.twig', [
            'entity' => $entity,
            'error' => $error,
        ]);
    }

    //    /**
    //     * 删除
    //     * @Route admin/setting/delete
    //     * @Method post
    //     */
    //    public function delete(Request $request)
    //    {
    //        $result = Setting::wherePk($request->get('id'))->delete();
    //
    //        if ($result) {
    //            Session::setFlash('message', '删除成功');
    //        } else {
    //            Session::setFlash('message', '删除失败');
    //        }
    //
    //        return Redirect::back();
    //    }

    //    /**
    //     * 详情
    //     * @Route admin/setting/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = Setting::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/setting/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }

    /**
     * 验证规则
     * @param string $scene create|update
     * @return array
     */
    protected function getRules($scene)
    {

        $rules = [
            [['name', 'key', 'value',], 'safe'],
            ['key', 'unique', 'table' => Setting::tableName(), 'field' => 'key'],
        ];

        if ($scene == 'update') {
            $rules = [
                [['value',], 'safe'],
            ];
        }

        return $rules;
    }

}
