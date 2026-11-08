<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Core\Validator;
use App\Core\Lang;

/** Lớp cha của mọi controller: chứa các hàm tiện ích nhỏ, không chứa nghiệp vụ. */
abstract class Controller
{
    /**
     * @param array<string, string> $params tham số trên đường dẫn (ví dụ ['committeeId' => '5'])
     */
    public function __construct(
        protected readonly Services $services,
        protected readonly Request $request,
        protected readonly array $params = [],
    ) {
    }

    /** Kiểm tra dữ liệu, trả về mảng lỗi theo tên trường (rỗng = hợp lệ). */
    protected function validate(array $data, array $rules): array
    {
        return (new Validator(Lang::current()))->validate($data, $rules);
    }

    protected function redirect(string $location): Response
    {
        return Response::redirect($location);
    }

    protected function notFound(): never
    {
        throw HttpException::notFound();
    }
}
