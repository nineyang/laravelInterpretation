<?php

namespace Illuminate\Foundation\Bootstrap;

use Exception;
use SplFileInfo;
use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $items = [];

//        查看bootstrap/cache/config.php文件是否存在，如果存在，就引入，并设置已经加载
        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

//        把Repository的实例放入instances的config中，这里后面会用到
        $app->instance('config', $config = new Repository($items));

//
        if (! isset($loadedFromCache)) {
//            设置配置信息
            $this->loadConfigurationFiles($app, $config);
        }

//       判断$this->items['app']['env']是否设置，如果没有设置就设置成production
        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');
        });

//        根据配置设置时区
        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @return void
     */
    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
//        引入config下面的配置文件
        $files = $this->getConfigurationFiles($app);

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

//        把文件的配置都设置在Repository对象的items中
        foreach ($files as $key => $path) {
            $repository->set($key, require $path);
        }
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];
//返回config的绝对路径
        $configPath = realpath($app->configPath());

//        这里的files，name ， 以及in都使用了链式调用，所以通过Finder::create()->files()->name('*.php')->in($configPath)返回的是一个Finder的实例，但是这个对象实现了IteratorAggregate接口，所以遍历的时候会调用getIterator返回了一个SqlFileInfo对象

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Get the configuration file nesting path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $configPath
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }
}
