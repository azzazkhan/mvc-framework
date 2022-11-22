<?php

namespace Illuminate\View;

use Illuminate\Support\Facades\File;

class View
{
    use Composable;

    /**
     * The name of the view.
     *
     * @var string
     */
    protected string $name;

    /**
     * The path to the view file.
     *
     * @var string
     */
    protected string $path;

    /**
     * The array of view data.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Creates a new view instance.
     * 
     * @param  string  $path
     * @param  array  $data
     * @return self
     */
    public function __construct(string $path, array $data = [])
    {
        $path = sprintf('%s/resources/views/%s.php', app('base_path'), str_replace('.', '/', $path));

        $this->path = $path;
        $this->name = File::name($path);
        $this->data = $data;
    }

    /**
     * Adds appropriate layout and child views and compiles markup.
     * 
     * @param  bool  $echo
     * @return string
     */
    public function render($echo = false): string
    {
        // Prepare this view's contents with dependencies
        $content = File::getBuffered($this->path, $this->data);

        // If this view has sub-views (slots) then render their content and
        // inject them in appropriate slot location
        if ($this->slots) {
            foreach ($this->slots as $name => $view) {
                $slotRegex = static::getSlotRegex($name);

                // Slot not defined in view template
                if (!preg_match($slotRegex, $content)) continue;

                // Replace the slot name with compiled view
                $content = preg_replace($slotRegex, $view->render(false), $content);
            }
        }

        // // If this view has a parent layout then get it's content and place
        // // current view's compiled markup with layout's default slot
        if (isset($this->layout)) {
            $layout = $this->layout->render(false);

            echo preg_replace(
                static::getSlotRegex($this->layout->getDefaultSlot()),
                $content,
                $layout
            );
        }

        if ($echo)
            print($content);

        return $content;
    }

    /**
     * Get dynamic RegEx for specified slot name.
     * 
     * @param  string  $slot
     * @return string
     *
     */
    private static function getSlotRegex(string $slot): string
    {
        return sprintf('/(\${{\s?%s\s?}})/', $slot);
    }

    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return self
     */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Get a piece of data from the view.
     *
     * @param  string  $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->with($key, $value);
    }

    /**
     * Check if a piece of data is bound to the view.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a piece of bound data from the view.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->render(false);
    }
}
