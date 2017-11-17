<?php
declare(strict_types = 1);

namespace ha\Middleware\Render\Twig;

use ha\Component\Configuration\Configuration;
use ha\Middleware\Render\HTMLRenderer;

class TwigBasedHTMLRenderer implements HTMLRenderer
{

    /** @var \ha\Component\Configuration\Configuration */
    private $cfg;

    /** @var \Twig_Environment */
    private $twigInstance;

    /**
     * Module constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->cfg = $configuration;
    }

    /**
     * Get value from internal configuration by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function cfg(string $key)
    {
        return $this->cfg->get($key);
    }

    /**
     * Get native twig.
     * @return \Twig_Environment
     */
    public function getNativeDriver()
    {
        if (!isset($this->twigInstance)) {

            // create loaders from configuration
            $loaders = [];
            foreach ($this->cfg('loaders') AS $loaderClass => $loaderParams) {
                $loaderClass = new \ReflectionClass($loaderClass);
                $loaders[] = $loaderClass->newInstanceArgs($loaderParams);
            }
            $mainLoader = new \Twig_Loader_Chain($loaders);
            $twigOptions = $this->cfg->get('options', false);
            if (!isset($twigOptions)) {
                $twigOptions = [];
            }
            $twig = new \Twig_Environment($mainLoader, $twigOptions);

            // add external functions from configuration
            $fnList = $this->cfg->get('functions', false);
            if (is_array($fnList)) {
                foreach ($fnList AS $fnName => $fn) {
                    $twig->addFunction(new \Twig_Function($fnName, $fn));
                }
            }

            // add predefined functions
            $fnList = $this->getPredefinedFunctions();
            foreach ($fnList AS $fnName => $fn) {
                $twig->addFunction(new \Twig_Function($fnName, $fn));
            }

            // store initialized template instance
            $this->twigInstance = $twig;
        }
        return $this->twigInstance;
    }

    /**
     * Get instance name.
     * @return string
     */
    final public function name(): string
    {
        return $this->cfg('name');
    }

    /** @inheritdoc */
    public function render(string $template, array $data): string
    {
        $HTML = $this->getNativeDriver()->render($template, $data);
        return $HTML;
    }

    /**
     * Add custom functions for twig templates.
     * @return array
     */
    private function getPredefinedFunctions(): array
    {
        return [
            'is_null' => function($value) {
                return is_null($value);
            },
            'is_not_null' => function($value) {
                return !is_null($value);
            },
            'is_int' => function($value) {
                return is_int($value);
            },
            'is_float' => function($value) {
                return is_float($value);
            },
            'is_string' => function($value) {
                return is_string($value);
            },
            'is_bool' => function($value) {
                return is_bool($value);
            },
            'is_array' => function($value) {
                return is_array($value);
            },
            'is_object' => function($value) {
                return is_array($value);
            },
            'is_datetime' => function($value) {
                return ($value instanceof \DateTime);
            },
            'is_true' => function($value) {
                return ($value === true);
            },
            'is_false' => function($value) {
                return ($value === false);
            },
            'throw_error' => function($message) {
                throw new \Error($message);
            },
        ];
    }
}