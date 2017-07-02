<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
//        这里是通过Container的call方法执行的
//        这两个方法主要是把两个路由文件中的内容载入
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
//        前面已经提过，Route继承自Facade，调用一个不存在的静态方法，会使用$app['router']的prefix来执行,
//        而$app['router']则会返回我们在最开始注册的基础服务提供者中的Routing/Router.php的实例
//        再加上prefix方法是受保护的，所以实例化的对象无法调用这个方法，会调用Router的__call方法
        Route::prefix('api')
//            所以Route::prefix('api')实际上返回的是一个RouteRegistrar的对象，当这个对象没有middleware这个方法时，会调用当前方法的__call方法
//                继续在attribute中设置middleware=>'api'
             ->middleware('api')
//            namespace同理
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
