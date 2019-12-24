<?php

namespace ApiBundle\Controller;

use Carbon\Carbon;
use Entity\Product;
use Entity\ProductCategory;
use Leaf\Json;
use Leaf\Request;
use Leaf\Validator;

/**
 * 产品管理
 * @author  curd generator
 * @since   1.0
 */
class ProductController
{
    /**
     * 列表
     *
     * @Route /api/product
     * @Method get
     *
     * @api {get} /api/product
     *
     * @apiParam {int} lang 语言 [可选] 默认 cn
     * @apiParam {int} page 页码 [可选] 默认1 eg. 1
     * @apiParam {int} page_size 每页记录数 [可选] 每页查询的条数,默认为20,最大不超过100 eg. 20
     * @apiParam {string} sortby 排序字段 [可选] 默认为id,支持排序的字段:id
     * @apiParam {string} order 排序方式 [可选] 默认降序, asc升序 desc降序
     * @apiSuccess
     *
     * ```
     * {
     *   "status": true,
     *   "data": {
     *     "page": {
     *       "itemCount": 总记录数,
     *       "currentPage": 当前页码,
     *       "pageSize": 每页条数,
     *       "pageCount": 共多少页,
     *     },
     *     "data": [
     *       {
     *         "id": ID,
     *         "lang": 语言,
     *         "category_id": 产品分类,
     *         "status": 发布 | 10 发布 | 20 隐藏,
     *         "recommend": 置顶 | 10 不推荐 | 20 推荐,
     *         "filename": 配图,
     *         "title": 名称,
     *         "description": 描述,
     *         "config": 主要配置,
     *         "content": 详细信息,
     *         "content1": 规格参数,
     *         "content2": 包装,
     *         "created_at": 创建时间,
     *         "updated_at": 更新时间,
     *       }
     *     ]
     *   },
     *   "code": "0"
     * }
     * ```
     * @apiError {"status":false, "data":"原因", code: "-1"}
     */
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'cn');

        //过滤条件
        $condition = [];
        $params = [];
        $search = $request->all();

        $condition[] = 'lang = :lang';
        $params[':lang'] = $lang;

        //if (!empty($search['id'])) {
        //    $condition[] = 'id = :id';
        //    $params[':id'] = trim($search['id']);
        //}

        //if (!empty($search['name'])) {
        //    $condition[] = 'name like :name';
        //    $params[':name'] = '%' . trim($search['name']) . '%';
        //}

        //分页与排序
        $pageSize = min(100, $request->get('page_size', 20));
        $sortBy = $request->get('sortby', 'id');
        $order = strtolower($request->get('order', 'desc'));

        if (!in_array($sortBy, ['id'])) {
            return Json::renderWithFalse('指定的排序字段不支持');
        }
        if (!in_array($order, ['asc', 'desc'])) {
            return Json::renderWithFalse('指定的排序方式不支持');
        }

        //数据
        $dataProvider = Product::where(implode(' and ', $condition), $params)
            ->orderBy($sortBy . ' ' . $order)
            ->paginate($pageSize);

        return Json::renderWithTrue($dataProvider);
    }

    /**
     * 详情
     * @Route /api/product/detail
     * @Method get
     *
     * @api {get} /api/product/detail
     * @apiParam {int} id
     * @apiSuccess
     *
     * ```
     * {
     *   "status": true,
     *   "data": {
     *      "id": ID,
     *      "lang": 语言,
     *      "category_id": 产品分类,
     *      "status": 发布 | 10 发布 | 20 隐藏,
     *      "recommend": 置顶 | 10 不推荐 | 20 推荐,
     *      "filename": 配图,
     *      "title": 名称,
     *      "description": 描述,
     *      "config": 主要配置,
     *      "content": 详细信息,
     *      "content1": 规格参数,
     *      "content2": 包装,
     *      "created_at": 创建时间,
     *      "updated_at": 更新时间,
     *   },
     *   "code": "0"
     * }
     * ```
     * @apiError {"status":false, "data":"原因", code: "-1"}
     */
    public function detail(Request $request)
    {
        /** @var Product $entity */
        $entity = Product::findByPk($request->get('id'));

        if ($entity == null) {
            return Json::renderWithFalse('指定数据不存在');
        }

        return Json::renderWithTrue($entity);
    }


    /**
     * 详情
     * @Route /api/product/category
     * @Method get
     *
     * @api {get} /api/product/category
     * @apiSuccess
     *
     * ```
     * {
     *   "status": true,
     *   "data": [
     *   {
     *      "id": ID,
     *      "name": 分类名称,
     *      "child": [],
     *   },
     *   {
     *      "id": ID,
     *      "name": 分类名称,
     *      "child": [],
     *   }
     *   ],
     *   "code": "0"
     * }
     * ```
     *
     * @apiError {"status":false, "data":"原因", code: "-1"}
     */
    public function category(Request $request)
    {
        $lang = $request->get('lang', 'cn');
        $category = ProductCategory::getTree($lang);
        return Json::renderWithTrue($category);
    }

}
