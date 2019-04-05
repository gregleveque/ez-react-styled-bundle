<?php

namespace Gie\EzReactStyledBundle\DependencyInjection\Compiler;

use Gie\EzReactStyledBundle\Command\GenerateWebpackFilesCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Gie\EzReactStyledBundle\Helper\FileGenerator;
use Exception;

class RendererCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('ez_react_styled');

        $serverBundlePath = $config['export_dir']
            . $config['server_bundle_dir']
            . '/'
            . $config['server_bundle_name']
            . '.js';

        $container
            ->getDefinition('limenius_react.phpexecjs_react_renderer')
            ->addMethodCall('setServerBundlePath', [$serverBundlePath]);
    }
}