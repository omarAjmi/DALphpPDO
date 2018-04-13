<?php
namespace Src\Config;

class ConfigManager
{
    private $configFilePath;
    private $configs;

    /**
     * Constructor
     *
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->configFilePath = !empty($filePath) ? $filePath : false;

        $this->configs = is_file($this->configFilePath) ? include $this->configFilePath : false;
    }
    /**
     * gets specified configuration from settings file
     *
     * @param string $poolName
     * @return array
     */
    public function get(string $poolName)
    {
        return $this->configs[$poolName];
    }

}
