<?php

namespace Illuminate\Http;

class Response
{
    /**
     * Sets status code for HTTP response.
     * 
     * @param  int  $code
     * @return void
     */
    public function setStatus(int $code = 200): void
    {
        http_response_code($code);
    }
}
