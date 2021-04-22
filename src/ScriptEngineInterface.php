<?php

declare(strict_types=1);

namespace Dakujem\Selectoo;

/**
 * Selectoo UI Script Engine Interface
 *
 * Engines produce UI scripts that control the Selectoo select input.
 * These scripts may use any available library or they can just be simple scripts doing something with the UI,
 * validating the input or loading data remotely. It's up to your imagination.
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
interface ScriptEngineInterface
{
    /**
     * Returns user interface script that controls the selectoo input.
     *
     *
     * @param mixed $control the instance of the Selectoo control
     * @return string|null   anything that can be type cast to string
     */
    function getUiScript($control);
}
