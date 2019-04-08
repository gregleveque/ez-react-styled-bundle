<?php

namespace Gie\EzReactStyledBundle\Twig;

use Limenius\ReactRenderer\Context\ContextProviderInterface;
use Limenius\ReactRenderer\Renderer\AbstractReactRenderer;
use Limenius\ReactRenderer\Renderer\StaticReactRenderer;
use Limenius\ReactRenderer\Twig\ReactRenderExtension;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollection;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use Twig\TwigFunction;

class ReactStyledExtension extends ReactRenderExtension
{
    /**
     * @var array
     */
    protected $components = [];

    /**
     * @var array
     */
    protected $styles = [];

    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @var array
     */
    protected $config;

    /**
     * Ajout de la fonction react_assets qui permet de gérer automatiquement l'import de scripts dans pied de page
     * @return array
     */
    public function getFunctions()
    {
        $functions = parent::getFunctions();
        $functions[] = new TwigFunction('react_scripts', [$this, 'generateHtml'], ['is_safe' => ['html']]);
        $functions[] = new TwigFunction('react_styles', [$this, 'stylesAnchor'], ['is_safe' => ['html']]);

        return $functions;
    }

    /**
     * @param TagRenderer $tagRenderer
     */
    public function setTagRenderer(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $componentName
     * @param array $options
     * @param bool $bufferData
     * @return string
     */
    public function reactRenderComponent($componentName, array $options = [], $bufferData = null)
    {
        $bufferData = $bufferData ?? $this->config['deferred_json_props'];
        return $this->reactRenderComponentArray($componentName, $options, $bufferData);
    }

    /**
     * @param string $componentName
     * @param array $options
     * @param bool $bufferData
     * @return string
     */
    public function reactRenderComponentArray($componentName, array $options = [], $bufferData = null)
    {
        $bufferData = $bufferData ?? $this->config['deferred_json_props'];

        if ($this->shouldRenderClientSide($options) && !$this->hasComponent($componentName)) {
            // On stocke dans un tableau le nom du composant associé à son chemin
            $this->components[] = $componentName;
        }

        /** @var $rendered array */
        $rendered = parent::reactRenderComponentArray($componentName, $options, $bufferData);

        if (isset($rendered['styles']) && !$this->hasStyle($rendered['styles'])) {
            $this->styles[] = $rendered['styles'];
        }

        return $rendered['componentHtml'];
    }

    /**
     *
     */
    public function stylesAnchor()
    {
        ob_start();
    }

    /**
     * @return string
     */
    public function generateHtml()
    {
        $content = ob_get_clean();
        $scripts = '';
        foreach ($this->components as $component) {
           $scripts .= $this->tagRenderer->renderWebpackScriptTags(
               $component,
               null,
               $this->config['entry_point_name']
           );
        }

        return join($this->styles) . $content . $this->reactFlushBuffer() . $scripts;
    }

    /**
     * @param $component
     * @return bool
     */
    private function hasComponent($component)
    {
        return in_array($component, $this->components);
    }

    /**
     * @param $style
     * @return bool
     */
    private function hasStyle($style)
    {
        return in_array($style, $this->styles)
            || $style === '<style data-styled-components=""></style>';
    }
}