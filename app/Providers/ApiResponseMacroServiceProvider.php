<?php

namespace BFACP\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class ApiResponseMacroServiceProvider
 * @package BFACP\Providers
 */
class ApiResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        response()->macro('success', function ($message, $data = []) {
            return response()->json([
                'message' => $message ?: 'OK',
                'data'    => $data,
                'errors'  => null,
            ]);
        });

        response()->macro('error', function ($message, $data = [], $errors = [], $status = 422) {
            return response()->json([
                'message' => $message ?: 'ERROR',
                'data'    => $data,
                'errors'  => $errors,
            ], $status);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
