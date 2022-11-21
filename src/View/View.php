<?php

namespace Illuminate\View;

class View
{
    /**
     * Get the contents of view.
     * 
     * @param  string  $path
     * @return string
     */
    public static function getContent(string $path): string
    {
        $view = sprintf(
            '%s/resources/views/%s.php',
            app('base_path'),
            str_replace('.', '/', $path)
        );

        ob_start();

        include_once $view;

        return ob_get_clean();
    }
}
