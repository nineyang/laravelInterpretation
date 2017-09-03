<?php

namespace Illuminate\Routing;

use Closure;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Pipeline\Pipeline as BasePipeline;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipeline extends BasePipeline
{
    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Exception $e) {
                return $this->handleException($passable, $e);
            } catch (Throwable $e) {
                return $this->handleException($passable, new FatalThrowableError($e));
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry()
    {
//        返回一个回调,而这里的回调会通过array_reduce来执行，所以其实返回的是这个回调里面的函数
//        $stack要注意的是，这里的$stack是上一次注入的回调，也就是本方法，来帮助后面的handle
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
//                    调用父类返回一个回调
                    $slice = parent::carry();
//                      执行这个回调再返回一个回调里面的回调函数
                    $callable = $slice($stack, $pipe);
//                      执行刚刚返回的第二层回调
                    return $callable($passable);
                } catch (Exception $e) {
                    return $this->handleException($passable, $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable, new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        if (! $this->container->bound(ExceptionHandler::class) || ! $passable instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        $response = $handler->render($passable, $e);

        if (method_exists($response, 'withException')) {
            $response->withException($e);
        }

        return $response;
    }
}
