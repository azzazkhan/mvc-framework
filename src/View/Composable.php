<?php

namespace Illuminate\View;

trait Composable
{

    /**
     * Sub-layout view slots.
     * 
     * @var array<\Illuminate\View\View>
     */
    protected array $slots = [];

    /**
     * The layout view.
     * 
     * @var \Illuminate\View\View
     */
    protected View $layout;

    /**
     * Default content slot for layout views.
     * 
     * @var string
     */
    protected string $defaultSlot = 'slot';

    /**
     * Sets view's parent layout view.
     * 
     * @param  string  $path
     * @return self
     */
    public function layout(string $path): self
    {
        $this->layout = new View($path);

        return $this;
    }


    /**
     * Adds layout slot for this view instance.
     * 
     * @param  string  $name
     * @param  \Illuminate\View\View  $view
     * @return self
     * 
     * @throws \InvalidArgumentException
     */
    public function addSlot(string $name, View $view): self
    {
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_-])$/', $name)) {
            throw new \InvalidArgumentException("The slot name [${name}] is not valid");
        }

        $this->slots[$name] = $view;

        return $this;
    }

    public function getDefaultSlot(): string
    {
        return $this->defaultSlot;
    }
}
