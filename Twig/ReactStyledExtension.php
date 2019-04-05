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
    protected $lookup;

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
     *  Import de la config Webpack et de l'emplacement du manifest en mode production
     * @param $bundle_path
     * @param $webpack
     */
    public function setConfig(TagRenderer $encoreLookup){

        $this->lookup = $encoreLookup;
    }



    /**
     * @param string $componentName
     * @param array $options
     * @return string
     */
    public function reactRenderComponent($componentName, array $options = [], $bufferData = false)
    {
        return $this->reactRenderComponentArray($componentName, $options, $bufferData);
    }

    /**
     * @param string $componentName
     * @param array $options
     * @return string
     */
    public function reactRenderComponentArray($componentName, array $options = [], $bufferData = false)
    {


        if ($this->shouldRenderClientSide($options) && !$this->hasComponent($componentName)) {
            // On stocke dans un tableau le nom du composant associé à son chemin
            $this->components[] = $componentName;
        }


        /**
         * @var $rendered array
         */
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
           $scripts .= $this->lookup->renderWebpackScriptTags($component, null, 'ez_react_styled');
        }

        return join($this->styles). $content . $scripts;
    }

    /**
     * Config Webpack : [
     *      'bundle1' => [ 'composant1', 'composant2']
     *      'bundle2' => [ 'coposant3', 'composant4']
     * ]
     *
     * On parcours les bundles definis dans la config
     * On met le path du composant faisant parti du bundle et dans la page
     * Si on a match autant de composant que le bundle on l'inclus et on supprime les composant orphelins
     *
     */
    private function reduceHtmlOutput()
    {
        $entry = $this->flatten($this->webpack['addEntry']);

        if (count($entry) <= 1) return;

        foreach($entry as $bundle => $components) {
            $match = array_reduce($components, function ($bundles, $component) {
                if (in_array($component, $this->components)) {
                    $bundles[] = $component;
                }
                return $bundles;
            }, []);

            if (count($match) == count($components)) {
                $this->components = array_diff($this->components, $match);
                $this->components[] = $bundle;
            }
        }
    }

    /**
     * @param $array
     * @return mixed
     */
    private function flatten($array)
    {
        return array_reduce(
            $array,
            function ($acc, $value) {
                return $acc[] = $value;
            }, []);
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

    /**
     * @param $component
     * @return string
     */
    private function generateTag($component) {
        $file = $this->webpack['setManifestKeyPrefix'].$component.'.js';
        return '<script src="'.$this->manifest[$file].'"></script>';
    }
}