<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\ProductCategory;
use Entity\Setting;
use Leaf\Application;
use Leaf\DB;
use Leaf\Request;
use Leaf\Session;
use Leaf\Util;
use Leaf\Validator;
use Leaf\View;
use Leaf\Redirect;

/**
 * 产品分类管理
 * @author  curd generator
 * @since   1.0
 */
class ProductCategoryController
{
    /**
     * 列表
     * @Route admin/product-category
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();

        $search = $request->get('ProductCategory');

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
        //        if (!empty($value) && in_array($key, array_keys(ProductCategory::labels()))) {
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
        //        $dataProvider = ProductCategory::where(implode(' and ', $condition), $params)
        //            ->orderBy($sortBy)
        //            ->paginate();

        //数据
        $list = ProductCategory::getAllChild(0);

        $entity = new ProductCategory();
        $entity->loadDefaultValues();
        //视图
        return View::render('@AdminBundle/product-category/index.twig', [
            //            'dataProvider' => $dataProvider,
            'list' => $list,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/product-category/create
     */
    public function create(Request $request)
    {
        $entity = new ProductCategory();
        $entity->loadDefaultValues();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('ProductCategory');

            if (Validator::validate($data, self::getRules('create'), ProductCategory::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                if (ProductCategory::insert($data)) {
                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/product-category');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }


        //视图
        return View::render('@AdminBundle/product-category/create.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => ProductCategory::getAllChild(),
        ]);
    }

    /**
     * 更新
     * @Route admin/product-category/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = ProductCategory::findByPkOrFail($request->get('id'));

        $categoryList = ProductCategory::getAllChild(0);

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('ProductCategory');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {

                $data['updated_at'] = Carbon::now();

                //更新
                if (ProductCategory::wherePk($entity->id)->update($data)) {
                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/product-category'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/product-category/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => $categoryList,

        ]);
    }

    /**
     * 删除
     * @Route admin/product-category/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        //删除子集
        $childIds = Util::arrayColumn(ProductCategory::getAllChild($request->get('id')), 'id');
        $bool = true;
        if (!empty($childIds)) {
            foreach ($childIds as $childId) {
                $bool = ProductCategory::wherePk($childId)->delete();
                if ($bool == false) {
                    break;
                }
            }
        }

        if ($bool == false) {
            Session::setFlash('message', '删除子分类失败');
            return Redirect::back();
        }

        $result = ProductCategory::wherePk($request->get('id'))->delete();

        if ($result) {
            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/product-category/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = ProductCategory::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/product-category/view.twig', [
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
        ];

        return $rules;
    }

}
