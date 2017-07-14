<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Foundation\Application;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
//        清空resolvedInstances数组
        Facade::clearResolvedInstances();

//        配置app
        Facade::setFacadeApplication($app);

//        首先通过$app->make('config')来获取前面在instances中注入的config,即repository，然后调用对象的config,再调用get方法获取app.aliases数组
//        getInstance会根据是否设置静态属性instances,然后返回一个AliasLoader的实例,在生成实例的时候，会把alias这个数组置于$this->instances中
        AliasLoader::getInstance($app->make('config')->get('app.aliases', []))->register();
    }
}
