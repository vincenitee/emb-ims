<?php

use App\Controllers\Api\BaseApiController;
use App\Models\NativeAdminModel;

class AdminAuthController extends BaseApiController {

    protected $modelName = NativeAdminModel::class;

    public function index() {

    }
}