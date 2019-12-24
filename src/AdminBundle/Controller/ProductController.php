<?php

namespace AdminBundle\Controller;

use Carbon\Carbon;
use Entity\Lang;
use Entity\Product;
use Entity\ProductCategory;
use Entity\ProductImage;
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
 * 产品管理
 * @author  curd generator
 * @since   1.0
 */
class ProductController
{

    use UploaderTrait;

    /**
     * 列表
     * @Route admin/product
     */
    public function index(Request $request)
    {
        //查询条件
        $condition = [];
        $params = [];

        $condition[] = 'lang = :lang';
        $params[':lang'] = Lang::getLang();
        $search = $request->get('Product');


        if (!empty($search['id'])) {
            $condition[] = 'id = :id';
            $params[':id'] = $search['id'];
            unset($search['id']);
        }

        if (!empty($search['category_id'])) {
            $condition[] = 'category_id = :category_id';
            $params[':category_id'] = $search['category_id'];
            unset($search['category_id']);
        }


        if (!empty($search['name'])) {
            $condition[] = 'name like :name';
            $params[':name'] = '%' . trim($search['name']) . '%';
            unset($search['name']);
        }

        //if (!empty($search)) {
        //    foreach ($search as $key => $value) {
        //        if (!empty($value) && in_array($key, array_keys(Product::labels()))) {
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
        $dataProvider = Product::where(implode(' and ', $condition), $params)
            ->orderBy('-recommend,-id')
            ->paginate();

        $categoryList = ProductCategory::getAllChild();

        $entity = new Product();
        $entity->loadDefaultValues();

        //视图
        return View::render('@AdminBundle/product/index.twig', [
            'dataProvider' => $dataProvider,
            'categoryList' => $categoryList,
            'entity' => $entity,
        ]);
    }

    /**
     * 新增
     * @Route admin/product/create
     */
    public function create(Request $request)
    {
        $entity = new Product();
        $entity->loadDefaultValues();
        $categoryList = ProductCategory::getAllChild();

        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Product');

            if (Validator::validate($data, self::getRules('create'), Product::labels())) {

                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();

                if ($insertId = Product::insertGetId($data)) {


                    //upload 处理新增的图片
                    $imageKeys = $request->get('fileKey', []);
                    $files = [];
                    foreach ($imageKeys as $imageKey) {
                        $file = $this->fileKeyToFilename($imageKey, ProductImage::uploadPath());
                        $files[] = $file;
                        if (empty($file)) {
                            continue;
                        }

                        $ProductImageInsertId = ProductImage::insertGetId([
                            'lang' => Lang::getLang(),
                            'product_id' => $insertId,
                            'filename' => $file,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);


                        UploadManager::clear(ProductImage::findByPk($ProductImageInsertId));

                    }


                    $img = ProductImage::where('product_id=:product_id', [':product_id' => $insertId])->findOne();
                    //首图作为封面
                    $data['filename'] = $img ? $img->filename : '';
                    Product::wherePk($insertId)->update($data);

                    //资源管理器:整理资源
                    UploadManager::clear(Product::findByPk($insertId), ['content']);

                    Session::setFlash('message', '添加成功');
                    return Redirect::to('admin/product');
                } else {
                    $error = '系统错误';
                }
            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/product/create.twig', [
            'entity' => $entity,
            'error' => $error,
            'categoryList' => $categoryList,
        ]);
    }

    /**
     * 更新
     * @Route admin/product/update
     */
    public function update(Request $request, Application $app)
    {

        //查询
        $entity = Product::findByPkOrFail($request->get('id'));
        $categoryList = ProductCategory::getAllChild();

        $images = $entity->getImages();


        //保存
        $error = '';
        if ($request->isMethod('POST')) {

            $data = $request->get('Product');

            //验证
            if (Validator::validate($data, self::getRules('update'))) {

                $data['updated_at'] = Carbon::now();

                //更新
                if (Product::wherePk($entity->id)->update($data)) {

                    //处理已删除的图片
                    $oldImgIds = Util::arrayColumn($images, 'id');
                    $keepImageIds = $request->get('imageIds', []);

                    $removeIds = array_diff($oldImgIds, $keepImageIds);
                    if (!empty($removeIds)) {

                        $list = ProductImage::whereIn('id', $removeIds)->findAll();
                        ProductImage::whereIn('id', $removeIds)->delete();
                        //资源管理器:整理资源
                        UploadManager::clear($list);


                    }

                    //处理新增的图片
                    $imageKeys = $request->get('fileKey', []);
                    foreach ($imageKeys as $imageKey) {
                        $file = $this->fileKeyToFilename($imageKey, ProductImage::uploadPath());
                        if (empty($file)) {
                            continue;
                        }
                        $ProductImageInsertId = ProductImage::insertGetId([
                            'lang' => Lang::getLang(),
                            'product_id' => $entity->id,
                            'filename' => $file,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        UploadManager::clear(ProductImage::findByPk($ProductImageInsertId));

                    }


                    $img = ProductImage::where('product_id=:product_id', [':product_id' => $entity->id])->findOne();
                    // 首图作为封面 $this->faceUrl()
                    $data['filename'] = $img ? $img->filename : '';
                    Product::wherePk($entity->id)->update($data);
                    //由于这里的Product 的 filename 不是上传来的，而是引用 ProductImage 的图片。所以不用检查这个字段
                    ////资源管理器:整理资源
                    UploadManager::clear($entity, ['content']);

                    Session::setFlash('message', '修改成功');
                    return Redirect::to($request->get('returnUrl', 'admin/product'));
                } else {
                    $error = '系统错误';
                }

            } else {
                $error = Validator::getFirstError();
            }
        }

        //视图
        return View::render('@AdminBundle/product/update.twig', [
            'entity' => $entity,
            'error' => $error,
            'images' => $images,
            'categoryList' => $categoryList,

        ]);
    }

    /**
     * 删除
     * @Route admin/product/delete
     * @Method post
     */
    public function delete(Request $request)
    {

        $entity = Product::findByPkOrFail($request->get('id'));
        $result = Product::wherePk($request->get('id'))->delete();
        if ($result) {
            //资源管理器:整理资源
            UploadManager::clear($entity);

            //清理轮播图
            //资源管理器:整理资源
            $list = ProductImage::where(['product_id' => $request->get('id')])->findAll();
            ProductImage::where(['product_id' => $request->get('id')])->delete();
            UploadManager::clear($list);

            Session::setFlash('message', '删除成功');
        } else {
            Session::setFlash('message', '删除失败');
        }

        return Redirect::back();
    }

    //    /**
    //     * 详情
    //     * @Route admin/product/view
    //     */
    //    public function view(Request $request, Application $app)
    //    {
    //        //查询
    //        $entity = Product::findByPkOrFail($request->get('id'));
    //
    //        //视图
    //        return View::render('@AdminBundle/product/view.twig', [
    //            'entity' => $entity,
    //        ]);
    //    }

    /**
     * 上传配图
     * @Route admin/product/upload
     */
    public function upload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('file', [], ProductImage::className());
        return Json::render($up);
    }


    /**
     * 富文本编辑器上传图片
     * @Route admin/product/um-upload
     */
    public function umUpload(Request $request, Application $app)
    {
        $up = $this->uploadToPath('upfile', [], Product::className());

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

    //    protected function getContentImages($content)
    //    {
    //
    /*        $pregRule = "/<[img|IMG].*?src=[\'|\"].*?uploads\/product(.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";*/
    //        preg_match_all($pregRule, $content, $matches);
    //
    //        if (!empty($matches)) {
    //            return $matches[1];
    //        }
    //
    //        return [];
    //
    //    }


    /**
     * 验证规则
     * @param string $scene create|update
     * @return array
     */
    protected function getRules($scene)
    {
        $rules = [
            [['category_id', 'status', 'recommend', 'title', 'description', 'config', 'content', 'content1', 'content2',], 'safe'],
            [['category_id', 'status', 'recommend',], 'integer'],
            [['category_id',], 'required'],
        ];

        return $rules;
    }

}
