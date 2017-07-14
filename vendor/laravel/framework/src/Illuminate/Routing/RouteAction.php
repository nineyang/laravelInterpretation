<?php

namespace Illuminate\Routing;

use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnexpectedValueException;

class RouteAction
{
    /**
     * Parse the given action into an array.
     *
     * @param  string  $uri
     * @param  mixed  $action
     * @return array
     */
    public static function parse($uri, $action)
    {
        // If no action is passed in right away, we assume the user will make use of
        // fluent routing. In that case, we set a default closure, to be executed
        // if the user never explicitly sets an action to handle the given uri.
//        如果没有对应动作，返回一个LogicException错误
        if (is_null($action)) {
//            这里会返回一个['uses' => function...]的数组
            return static::missingAction($uri);
        }

        // If the action is already a Closure instance, we will just set that instance
        // as the "uses" property, because there is nothing else we need to do when
        // it is available. Otherwise we will need to find it in the action list.
//        如果是一个回调，拼接一个数组返回
        if (is_callable($action)) {
            return ['uses' => $action];
        }

        // If no "uses" property has been set, we will dig through the array to find a
        // Closure instance within this list. We will set the first Closure we come
        // across into the "uses" property that will get fired off by this route.
        elseif (! isset($action['uses'])) {
//            返回action的第一个回调,如果都不是，返回null
            $action['uses'] = static::findCallable($action);
        }

//        判断$action['uses']中是否存在@
        if (is_string($action['uses']) && ! Str::contains($action['uses'], '@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

    /**
     * Get an action for a route that has no action.
     *
     * @param  string  $uri
     * @return array
     */
    protected static function missingAction($uri)
    {
        return ['uses' => function () use ($uri) {
            throw new LogicException("Route for [{$uri}] has no action.");
        }];
    }

    /**
     * Find the callable in an action array.
     *
     * @param  array  $action
     * @return callable
     */
    protected static function findCallable(array $action)
    {
//        first会遍历$action，如果他的value是一个回调并且key是一个值，那么就返回这个回调
        return Arr::first($action, function ($value, $key) {
            return is_callable($value) && is_numeric($key);
        });
    }

    /**
     * Make an action for an invokable controller.
     *
     * @param  string $action
     * @return string
     */
    protected static function makeInvokable($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException("Invalid route action: [{$action}].");
        }

        return $action.'@__invoke';
    }
}
