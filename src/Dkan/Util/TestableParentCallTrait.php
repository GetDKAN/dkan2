<?php

namespace Dkan;

/**
 * Trait allows for simpler testing of `parent::` calls
 * 
 * Usage:
 * 
 *  In subject under test, instead of using `parent::method($arg1, $arg2)`,
 *  use `$this->parent($arg1, $arg2)`
 * 
 * @author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
Trait TestableParentCall {

    /**
     * Wrapper for unit testing.
     * 
     * Implementation is compatible with PHP 5.4+
     * 
     * @codeCoverageIgnore
     * @param string $method Method name.
     * @param variable-length ...$args
     * @return mixed
     */
    protected function parent($method, ...$args) {
        return parent::$method(...$args);
    }
}