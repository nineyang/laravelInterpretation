<?php

namespace Illuminate\Pipeline;

use Closure;
use RuntimeException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Pipeline as PipelineContract;

class Pipeline implements PipelineContract
{
    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Create a new class instance.
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
//        这里需要注意的是，这里的$this->carry()和$this->prepareDestination都是调用的子类的方法，子类是Routing/Pipeline.php
//        array_reverse将$this->pipes顺序颠倒，
//        array_reduce 如果制定了第三个参数，那么在第一个参数不为空的情况下，会作为array_reverse第二个参数，也就是我们设置的回调函数的第一次执行时的第一个参数，如果为空的情况下，那么就会作为这个函数的返回值返回
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
        );
//   $pipeline实际上获取的是一个$this->carry()返回的第二层的回调函数，下面才正式执行
        return $pipeline($this->passable);
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry()
    {
//        这里最终会被他的子类来调用执行回调
        return function ($stack, $pipe) {

            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                } elseif (! is_object($pipe)) {
//                    因为我们传入的都是类名，所以走的是这个条件判断
                    list($name, $parameters) = $this->parsePipeString($pipe);
//                    通过make，获取一个实例
                    $pipe = $this->getContainer()->make($name);
//这里后面会传入一个request对象，作为参数合并
                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    $parameters = [$passable, $stack];
                }
//                调用生成的实例的handle方法
                return $pipe->{$this->method}(...$parameters);
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
//        explode的第三个参数，如果是正数，则表示最多切割成指定长度的数组，如果是负数，则除了倒数指定位置以外的都被切割
//        array_pad，指定长度是2，如果没有，则用[]填充
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Get the container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }
}
