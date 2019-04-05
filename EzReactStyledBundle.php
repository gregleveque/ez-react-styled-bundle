<?php

namespace Gie\EzReactStyledBundle;

use Gie\EzReactStyledBundle\DependencyInjection\Compiler\RendererCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EzReactStyledBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RendererCompilerPass());
    }
}
