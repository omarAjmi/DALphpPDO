<?php
namespace Src\Config;

class ConfigManager
{
    private $configFilePath;
    private $options = [];
    private $configs = [];
    private $drivers = ['PHP', 'YML', 'JASON', 'INI'];
    private $defaultPool = 'mysql';

    /**
     * Constructor
     *
     * @param string $filePath
     * @param string $location
     */
    public function __construct(string $filePath, string $location = null)
    {
        try {
            $argsNum = func_num_args();
            if ($argsNum > 2) {
                throw new Exception("SETUP ERROR: configuration manager can accept only up to 2 parameters,'$argsNum' given!");
            }
            $this->configureOptions($filePath, $location);
            $this->parseConfigs();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * sets the configs property like this:
     * [
     *   [driver] => PHP|YML|JASON|INI
     *   [filename] => filename
     *   [directory] => configs file path
     * ]
     *
     * @param string $fileName
     * @param string $location
     * @return void
     */
    private function configureOptions(string $fileName, string $location = null)
    {
        try {
            if (!isset($filename, $location) or !is_string($filename) or !is_string($location)) {
                throw new \Exception("configuring options takes only strings as parameters");
            }
            $default = [
                'driver' => 'PHP',
                'filename' => null,
                'directory' => __DIR__,
            ];
            $options = [];
            if ($location)
                $options['directory'] = rtrim($this->normalize($location), DIRECTORY_SEPARATOR);
            else {
                if (basename($file) !== $file)
                    $options['directory'] = rtrim($this->normalize(pathinfo($file, PATHINFO_DIRNAME)), DIRECTORY_SEPARATOR);
            }
            $options['filename'] = basename($file);
            if (strpos($options['filename'], '.') !== false)
                $options['driver'] = strtoupper(pathinfo($options['filename'], PATHINFO_EXTENSION));
            else
                $options['filename'] = $options['filename'] . '.' . strtolower($default['driver']);
            if (!in_array($options['driver'], $this->drivers))
                throw new Exception('ERROR: driver "' . $options['driver'] . '" not supported');
            $this->options = array_merge($default, $options);
            return $this->options;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * returns the absolute configs file path
     *
     * @param string $path
     * @param string $relativeTo
     * @return string
     */
    private function normalize(string $path, string $relativeTo = null)
    {
        $path = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $isAbsolute = stripos(PHP_OS, 'win') === 0 ? preg_match('/^[A-Za-z]+:/', $path) : !strncmp($path, DIRECTORY_SEPARATOR, 1);
        if (!$isAbsolute) {
            if (!$relativeTo) $relativeTo = getcwd();
            $path = $relativeTo . DIRECTORY_SEPARATOR . $path;
        }
        if (is_link($path) and ($parentPath = realpath(dirname($path))))
            return $parentPath . DIRECTORY_SEPARATOR . $path;
        if ($realpath = realpath($path)) return $realpath;
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        while (end($parts) !== false) {
            array_pop($parts);
            $attempt = stripos(PHP_OS, 'win') === 0 ? implode(DIRECTORY_SEPARATOR, $parts) : DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
            if ($realpaths = realpath($attempt)) {
                $path = $realpaths . substr($path, strlen($attempt));
                break;
            }
        }
        return $path;
    }

    /**
     * sets the options property to an array containing all the drivers parameters
     *
     * @param array $opts
     * @return array
     */
    private function parseConfigs(array $opts = [])
    {
        try {
            $this->configFilePath = $this->normalize($opts['directory'] . DIRECTORY_SEPARATOR . $opts['filename']);
            if (!file_exists($this->configFilePath))
                file_put_contents($this->configFilePath, '', LOCK_EX);
            switch ($this->options['driver']) {
                case 'JSON':
                    $this->configs = unserialize(json_decode(file_get_contents($this->configFilePath), true));
                    break;
                case 'INI':
                    $this->configs = parse_ini_file($this->configFilePath, true);
                    break;
                case 'YML':
                    $ndocs = 0;
                    $this->configs = yaml_parse_file($this->configFilePath, 0, $ndocs);
                    break;
                default:
                    if (!$this->configs = include $this->configFilePath) $this->configs = [];
                    break;
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $this->configs;
    }

    /**
     * gets a spesific driver configs parameters
     *
     * @param string $poolName
     * @param string $parameter
     * @return mixed
     */
    public function get(string $poolName = null, string $parameter = null)
    {
        if ($parameter) $parameter = trim(strtolower($parameter));
        if ($poolName) $poolName = trim(strtolower($poolName));
        if (!count($this->configs)) return false;
        if (!$poolName or !strlen($poolName)) return $this->configs;
        if ($poolName and $parameter) {
            if (!isset($this->configs[$poolName])) {
                $value = $parameter;
                $parameter = $poolName;
                $poolName = $this->defaultPool;
                if (!isset($this->configs[$poolName][$parameter][$value]))
                    return false;
                return $this->configs[$poolName][$parameter][$value];
            }
        } elseif (!$parameter or !strlen($parameter)) {
            $parameter = $poolName;
            if (isset($this->configs[$parameter])) return $this->configs[$parameter];
            $poolName = $this->defaultPool;
        }
        if (!isset($this->configs[$poolName][$parameter])) return false;
        return $this->configs[$poolName][$parameter];
    }

    /**
     * sets a value toa spesific pool parameter
     *
     * @param string $poolName
     * @param string $parameter
     * @param int|string
     * @return bool
     */
    public function set($poolName, $parameter = null, $value = null)
    {
        ob_start();
        $numarg = func_num_args();
        $arguments = func_get_args();
        switch ($numarg) {
            case 1:
                if (!is_array($arguments[0])) return false;
                $parameter = array_change_key_case($arguments[0], CASE_LOWER);
                $poolName = null;
                $value = null;
                break;
            case 2:
                if (is_array($arguments[0])) return false;
                $_arg = strtolower(trim($arguments[0]));
                if (is_array($arguments[1])) {
                    $poolName = $_arg;
                    $parameter = array_change_key_case($arguments[1], CASE_LOWER);
                    $value = null;
                } else {
                    $parameter = $_arg;
                    $value = $arguments[1];
                    $poolName = null;
                }
                break;
            default:
                break;
        }
        $poolName = $poolName ? trim(strtolower($poolName)) : $this->defaultPool;
        if (!is_array($parameter)) {
            if (!$value) return false;
            $parameter = trim(strtolower($parameter));
            if (!isset($this->configs[$poolName][$parameter]) or !is_array($this->configs[$poolName][$parameter])) :
                $this->configs[$poolName][$parameter] = $value;
            else :
                if (!is_array($value)) $value = array($value);
            $this->configs[$poolName][$parameter] = array_merge($this->configs[$poolName][$parameter], $value);
            endif;
        } else {
            if ($value) return false;
            $parameter = array_change_key_case($parameter, CASE_LOWER);
            $sectionsize = count($this->configs[$poolName]);
            $itemsize = count($parameter);
            if ($sectionsize) {
                if ($itemsize == '1') {
                    if (isset($this->configs[$poolName][key($parameter)]))
                        $this->configs[$poolName][key($parameter)] = array_merge($this->configs[$poolName][key($parameter)], $parameter[key($parameter)]);
                    else if (!is_numeric(key($parameter))) $this->configs[$poolName][key($parameter)] = $parameter[key($parameter)];
                } else $this->configs[$poolName] = array_merge($this->configs[$poolName], $parameter);
            } else $this->configs[$poolName] = $parameter;
        }
        $re = $this->Save();
        ob_end_clean();
        return $re;
    }

    /**
     * deletes a spesific parameter from a givien pool
     *
     * @param string $poolName
     * @param string $parameter
     * @return bool
     */
    public function del(string $poolName, string $parameter = null)
    {
        $poolName = trim(strtolower($poolName));
        if ($parameter and strlen($parameter)) {
            $parameter = trim(strtolower($parameter));
            if (!isset($this->configs[$poolName])) {
                $key = $parameter;
                $parameter = $poolName;
                $poolName = $this->defaultPool;
                if (isset($this->configs[$poolName][$parameter][$key])) {
                    $itemSize = count($this->configs[$poolName][$parameter]);
                    if ($itemSize > 1) unset($this->configs[$poolName][$parameter][$key]);
                    else unset($this->configs[$poolName]);
                }
            } else {
                $sectionSize = count($this->configs[$poolName]);
                if (isset($this->configs[$poolName][$parameter])) {
                    if ($sectionSize > 1) unset($this->configs[$poolName][$parameter]);
                    else unset($this->configs[$poolName]);
                }
            }
        } else {
            $parameter = $poolName;
            if (!isset($this->configs[$parameter])) {
                $poolName = $this->defaultPool;
                $defaultSectionSize = count($this->configs[$poolName]);
                if (isset($this->configs[$poolName][$parameter])) {
                    if ($defaultSectionSize > 1) unset($this->configs[$poolName][$parameter]);
                    else unset($this->configs[$poolName]);
                }
            } else unset($this->configs[$parameter]);
        }
        return $this->Save();
    }

    /**
     * saves changes to the settings file
     *
     * @return void
     */
    private function Save()
    {
        if (!is_writeable($this->configFilePath)) @chmod($this->configFilePath, 0775);
        $content = null;
        switch ($this->Options['driver']) {
            case 'JSON':
                $content .= json_encode(serialize($this->configs));
                break;
            case 'INI':
                $content .= '; @file generator: Omar A.Ajmi "' . get_class($this) . '" Class' . PHP_EOL;
                $content .= '; @Last Update: ' . date('Y-m-d H:i:s') . PHP_EOL;
                $content .= PHP_EOL;
                foreach ($this->configs as $section => $array) {
                    is_array($array) or $array = array($array);
                    $content .= '[' . $section . ']' . PHP_EOL;
                    foreach ($array as $key => $value)
                        $content .= PHP_TAB . $key . ' = ' . $value . PHP_EOL;
                    $content .= PHP_EOL;
                }
                break;
            case 'YML':
                $content .= yaml_emit($this->configs, YAML_UTF8_ENCODING, YAML_LN_BREAK);
                break;
            default:
                $content .= '<?php' . PHP_EOL;
                $content .= 'return ';
                $content .= var_export($this->configs, true) . ';';
                $content = preg_replace('/array\s+\(/', '[', $content);
                $content = preg_replace('/,(\s+)\)/', '$1]', $content);
                break;
        }
        file_put_contents($this->configFilePath, $content, LOCK_EX);
        @chmod($this->configFilePath, 0644);
        return true;
    }
}