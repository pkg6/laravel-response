<?php

/*
 * This file is part of the jiannei/laravel-response.
 *
 * (c) Jiannei <longjian.huang@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jiannei\Response\Laravel\Support\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

trait JsonResponseTrait
{
    /**
     *  Respond with an accepted response and associate a location and/or content if provided.
     *
     * @param  array  $data
     * @param  string  $message
     * @param  string  $location
     * @return JsonResponse|JsonResource
     */
    public function accepted($data = [], string $message = '', string $location = '')
    {
        $response = $this->success($data, $message, 202);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a created response and associate a location if provided.
     *
     * @param  null  $data
     * @param  string  $message
     * @param  string  $location
     * @return JsonResponse|JsonResource
     */
    public function created($data = [], string $message = '', string $location = '')
    {
        $response = $this->success($data, $message, 201);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a no content response.
     *
     * @param  string  $message
     * @return JsonResponse|JsonResource
     */
    public function noContent(string $message = '')
    {
        return $this->success([], $message, 204);
    }

    /**
     * Alias of success method, no need to specify data parameter.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array  $headers
     * @param  int  $option
     * @return JsonResponse|JsonResource
     */
    public function ok(string $message = '', int $code = 200, array $headers = [], int $option = 0)
    {
        return $this->success([], $message, $code, $headers, $option);
    }

    /**
     * Alias of the successful method, no need to specify the message and data parameters.
     * You can use ResponseCodeEnum to localize the message.
     *
     * @param  int  $code
     * @param  array  $headers
     * @param  int  $option
     * @return JsonResponse|JsonResource
     */
    public function localize(int $code = 200, array $headers = [], int $option = 0)
    {
        return $this->ok('', $code, $headers, $option);
    }

    /**
     * Return a 400 bad request error.
     *
     * @param  string|null  $message
     */
    public function errorBadRequest(string $message = '')
    {
        $this->fail($message, 400);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param  string  $message
     */
    public function errorUnauthorized(string $message = '')
    {
        $this->fail($message, 401);
    }

    /**
     * Return a 403 forbidden error.
     *
     * @param  string  $message
     */
    public function errorForbidden(string $message = '')
    {
        $this->fail($message, 403);
    }

    /**
     * Return a 404 not found error.
     *
     * @param  string  $message
     */
    public function errorNotFound(string $message = '')
    {
        $this->fail($message, 404);
    }

    /**
     * Return a 405 method not allowed error.
     *
     * @param  string  $message
     */
    public function errorMethodNotAllowed(string $message = '')
    {
        $this->fail($message, 405);
    }

    /**
     * Return a 500 internal server error.
     *
     * @param  string  $message
     */
    public function errorInternal(string $message = '')
    {
        $this->fail($message);
    }

    /**
     * Return an fail response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|null  $errors
     * @param  array  $header
     * @param  int  $options
     * @return JsonResponse
     *
     * @throws HttpResponseException
     */
    public function fail(string $message = '', int $code = 500, $errors = null, array $header = [], int $options = 0)
    {
        $response = $this->response(
            $this->formatter->data(null, $message, $code, $errors),
            Config::get('response.error_code') ?: $code,
            $header,
            $options
        );

        if (is_null($errors)) {
            $response->throwResponse();
        }

        return $response;
    }

    /**
     * Return a success response.
     *
     * @param  JsonResource|array|mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @param  array  $headers
     * @param  int  $option
     * @return JsonResponse|JsonResource
     */
    public function success($data = [], string $message = '', int $code = 200, array $headers = [], int $option = 0)
    {
        if ($data instanceof ResourceCollection) {
            return tap(
                $this->response($this->formatter->resourceCollection(...func_get_args()), $code, $headers, $option),
                function ($response) use ($data) {
                    $response->original = $data->resource->map(
                        function ($item) {
                            return is_array($item) ? Arr::get($item, 'resource') : $item->resource;
                        }
                    );

                    $data->withResponse(request(), $response);
                }
            );
        }

        if ($data instanceof JsonResource) {
            return tap(
                $this->response($this->formatter->jsonResource(...func_get_args()), $code, $headers, $option),
                function ($response) use ($data) {
                    $response->original = $data->resource;

                    $data->withResponse(request(), $response);
                }
            );
        }

        if ($data instanceof AbstractPaginator) {
            return $this->response($this->formatter->paginator(...func_get_args()), $code, $headers, $option);
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        return $this->response($this->formatter->data(Arr::wrap($data), $message, $code), $code, $headers, $option);
    }

    /**
     * Return a new JSON response from the application.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return JsonResponse
     */
    protected function response($data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $this->formatter->statusCode($status), $headers, $options);
    }
}
