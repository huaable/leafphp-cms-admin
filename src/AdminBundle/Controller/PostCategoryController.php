<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\PostCategory;
use Leaf\Application;
use Leaf\DB;
use Leaf\Request;
use Leaf\Session;
use Leaf\Util;
use Leaf\Validator;
use Leaf\View;
use Leaf\Redirect;

/**
 * 文章分类管理
 * @author  curd generator
 * @since   1.0
 */
class PostCategoryController
{
    /**
     * 列表
     * @Route admin/post-category
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];
        $search = $request->get('PostCategory');

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
        //        if (!empty($value) && in_array($key, array_keys(PostCategory::labels()))) {
        //            $condition[] = '`' . $key . '` like :' . $key;
        //            $params[':' . $key] = '%' . trim($search[$key]) . '%';
        //        }
        //    }
        //}

        $sortBy = $request->get('sortby', '-id');
        if (!in_array(ltrim($sortBy, '-'), ['id', 'weight'])) {
            Session::setFlash('message', '指定字段不支持排序');
            return Redirect::back();
        }

        //数据
//                $dataProvider = PostCategory::where(implode(' and ', $condition), $params)
//                    ->orderBy($sortBy)
//                    ->paginate();

        //数据
        $list = PostCategory::getAllChild(0);
        $entity = new PostCategory();
        $entity->loadDefaultValues();
        //视图
        return View::render('@AdminBundle/post-category/index.twig', [
            'list' => $list,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/post-category/create
     */
    public function create(Request $request)
    {
        $entity = new PostCategory();
        $entity->loadDefaultValues();

        $categoryList = PostCategory::getAllChild(0);

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('PostCategory');

            if (Validator::validate($data, self::getRules('create'), PostCategory::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                if (PostCategory::insert($data)) {
                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/post-category');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/post-category/create.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => $categoryList,
        ]);
    }

    /**
     * 更新
     * @Route admin/post-category/update
     */
    public function update(Request $request, Application $app)
    {

        if ($request->get('id') < 1001) {
            Session::setFlash('message', '系统分类不支持修改');
            return Redirect::to($request->get('returnUrl', 'admin/post-category'));
        }

        //查询
        $entity = PostCategory::findByPkOrFail($request->get('id'));

        $categoryList = PostCategory::getAllChild(0);


        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('PostCategory');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {

                $data['updated_at'] = Carbon::now();

                //更新
                if (PostCategory::wherePk($entity->id)->update($data)) {
                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/post-category'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/post-category/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => $categoryList,
        ]);
    }

    /**
     * 删除
     * @Route admin/post-category/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        if ($request->get('id') < 1001) {
            Session::setFlash('message', '系统分类不支持修改');
            return Redirect::to($request->get('returnUrl', 'admin/post-category'));
        }

        //删除子集
        $childIds = Util::arrayColumn(PostCategory::getAllChild($request->get('id')),'id');

        $bool = true;
        if (!empty($childIds)) {
            foreach ($childIds as $childId) {
                $bool = PostCategory::wherePk($childId)->delete();
                if ($bool == false) {
                    break;
                }
            }
        }

        if ($bool == false) {
            Session::setFlash('message', '删除子分类失败');
            return Redirect::back();
        }


        $result = PostCategory::wherePk($request->get('id'))->delete();

        if ($result) {
            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/post-category/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = PostCategory::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/post-category/view.twig', [
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
            [['name', 'weight', 'parent_id',], 'safe'],
            [['weight', 'parent_id',], 'integer'],
        ];

        return $rules;
    }

}
