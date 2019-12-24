<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\ProductBanner;
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
use Service\UploaderTrait;

/**
 * 横幅广告管理
 * @author  curd generator
 * @since   1.0
 */
class ProductBannerController
{
    use UploaderTrait;

    /**
     * 列表
     * @Route admin/product-banner
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();

        $search = $request->get('ProductBanner');

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
        //        if (!empty($value) && in_array($key, array_keys(ProductBanner::labels()))) {
        //            $condition[] = '`' . $key . '` like :' . $key;
        //            $params[':' . $key] = '%' . trim($search[$key]) . '%';
        //        }
        //    }
        //}

        //        $sortBy = $request->get('sortby', '-id');
        //        if (!in_array(ltrim($sortBy, '-'), ['id'])) {
        //            Session::setFlash('message', '指定字段不支持排序');
        //            return Redirect::back();
        //        }

        //数据
        $dataProvider = ProductBanner::where(implode(' and ', $condition), $params)
            ->orderBy('-weight,-id')
            ->paginate();

        $entity = new ProductBanner();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/product-banner/index.twig', [
            'dataProvider' => $dataProvider,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/product-banner/create
     */
    public function create(Request $request)
    {
        $entity = new ProductBanner();
        $entity->loadDefaultValues();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('ProductBanner');

            if (Validator::validate($data, self::getRules('create'), ProductBanner::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                //处理新增的图片
                $imageKeys = $request->get('fileKey', []);
                foreach ($imageKeys as $imageKey) {
                    $file = $this->fileKeyToFilename($imageKey, ProductBanner::uploadPath());
                    if (empty($file)) {
                        continue;
                    }
                    $data['filename'] = $file;
                }

                if ($insertId = ProductBanner::insertGetId($data)) {
                    //资源管理器:整理资源
                    UploadManager::clear(ProductBanner::findByPk($insertId));

                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/product-banner');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }


        //视图
        return View::render('@AdminBundle/product-banner/create.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => []
        ]);
    }

    /**
     * 更新
     * @Route admin/product-banner/update
     */
    public function update(Request $request, Application $app)
    {
        //查询
        $entity = ProductBanner::findByPkOrFail($request->get('id'));
        $images = [$entity];

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('ProductBanner');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {



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
                    $file = $this->fileKeyToFilename($imageKey, ProductBanner::uploadPath());
                    if (empty($file)) {
                        continue;
                    }

                    $data['filename'] = $file;
                }

                $data['updated_at'] = Carbon::now();

                //更新
                if (ProductBanner::wherePk($entity->id)->update($data)) {
                    //资源管理器:整理资源
                    UploadManager::clear($entity);
                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/product-banner'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/product-banner/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => $images,
        ]);
    }

    /**
     * 删除
     * @Route admin/product-banner/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        $id = $request->get('id');
        $entity = ProductBanner::findByPkOrFail($id);
        $result = ProductBanner::wherePk($id)->delete();
        UploadManager::clear($entity);

        if ($result) {
            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    /**
     * 上传配图
     * @Route admin/product-banner/upload
     */
    public function upload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('file', [], ProductBanner::className());
        return Json::render($up);
    }

    //    /**
    //     * 详情
    //     * @Route admin/product-banner/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = ProductBanner::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/product-banner/view.twig', [
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
            [['product_id', 'weight'], 'safe'],
        ];

        return $rules;
    }

}
