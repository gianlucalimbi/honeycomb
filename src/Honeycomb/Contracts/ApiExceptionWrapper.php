<?php

namespace Honeycomb\Contracts;

interface ApiExceptionWrapper
{

    /**
     * Wrap given Exception in an ApiException.
     *
     * @param \Throwable|\Exception $exception
     *
     * @return \Honeycomb\ApiException
     */
    public function wrap($exception);

}
