<?php

namespace Suez\Bundle\PrometheusMonitoringBundle\Monitoring\Metric;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TweedeGolf\PrometheusClient\CollectorRegistry as PrometheusCollectorRegistry;
use Suez\Bundle\PrometheusMonitoringBundle\Monitoring\Metric\Collector\AbstractCollector;

/**
 * Class CollectorRegistry
 *
 * The registry of data collectors in the API
 *
 * @package Suez\Bundle\PrometheusMonitoringBundle\Monitoring
 */
class CollectorRegistry
{
    /**
     * @var AbstractCollector[]
     */
    protected $collectors = [];

    /**
     * Registry of collector to translate and save collected metrics into Prometheus format
     *
     * @var PrometheusCollectorRegistry
     */
    protected $registry;

    /**
     * The current name to label metrics
     *
     * @var string
     */
    protected $routeName;

    /**
     * The code of the App to label metrics
     * @var string
     */
    protected $appCode;

    /**
     * CollectorRegistry constructor.
     *
     * @param string $appCode
     * @param PrometheusCollectorRegistry $registry
     */
    public function __construct(string $appCode, PrometheusCollectorRegistry $registry)
    {
        $this->appCode = $appCode;
        $this->registry = $registry;
    }

    /**
     * Add a collector to the registry
     *
     * @param AbstractCollector $collector
     */
    public function addCollector(AbstractCollector $collector)
    {
        $this->collectors[] = $collector;
    }

    /**
     * Trigger data collection on all collectors
     *
     * @param Request $request
     * @param Response $response
     */
    public function collect(Request $request, Response $response)
    {
        foreach ($this->collectors as $collector) {
            $collector->collect($request, $response);
        }
    }

    /**
     * Set the current route name
     *
     * @param string $routeName
     */
    public function setCurrentRoute($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * Save the collected metrics to the backend in Prometheus format
     *
     * @throws \TweedeGolf\PrometheusClient\PrometheusException
     */
    public function save()
    {
        foreach ($this->collectors as $collector) {
            $collector->save(
                $this->registry->get($collector->getCollectorName()),
                [$this->appCode, $this->routeName]
            );
        }

        $this->registry->getCounter('suez_sf_app_call_count')->inc(
            1,
            [$this->appCode, $this->routeName]
        );
    }
}