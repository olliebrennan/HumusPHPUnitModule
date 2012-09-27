<?php

namespace HumusPHPUnitModule\ModuleManager\Listener;

use HumusPHPUnitModule\ModuleManager\Feature\PHPUnitProviderInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\Stdlib\ArrayUtils;

class PHPUnitListener implements ListenerAggregateInterface
{

    /**
     * @var array
     */
    protected $paths = array();

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @return PHPUnitListener
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE, $this);
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, array($this, 'onLoadModulesPost'), 1000);
    }

    /**
     * Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     * @return PHPUnitListener
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $key => $listener) {
            $events->detach($listener);
            unset($this->listeners[$key]);
        }
        $this->listeners = array();
        return $this;
    }

    /**
     * @param  ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getParam('module');

        if (!$module instanceof PHPUnitProviderInterface
            && !method_exists($module, 'getPHPUnitXmlPath')
        ) {
            return;
        }

        $phpUnitXmlPath = $module->getPHPUnitXmlPaths();
        $this->addPHPUnitXmlPath($e->getModuleName(), $phpUnitXmlPath);

    }

    /**
     * Merge PHPUnitListener results with PHPUnit Runner config
     *
     * @param ModuleEvent $e
     * @return PHPUnitListener
     */
    public function onLoadModulesPost(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config = $configListener->getMergedConfig(false);
        $config['humus_phpunit_module']['phpunit_runner'] = ArrayUtils::merge($config['humus_phpunit_module']['phpunit_runner'], $this->getPaths());
        $configListener->setMergedConfig($config);
        return $this;
    }

    /**
     * Add phpunit xml path
     *
     * @param string $path
     * @return PHPUnitListener
     */
    public function addPHPUnitXmlPath($moduleName, $path)
    {
        $this->paths[$moduleName] = $path;
        return $this;
    }

    /**
     * Get phpunit xml paths
     *
     * - key is module name
     * - value is array of paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

}