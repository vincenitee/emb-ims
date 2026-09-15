<?php

namespace App\Controllers\Api;

use App\Models\CarhrisUserModel;

class AuthController extends BaseApiController {
    public function test() {
        return (new CarhrisUserModel())->findByUsername('Cgd');
    }
}
