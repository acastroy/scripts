<?php

/**
 * Class KimaiInstaller
 *
 * Copyright Kevin Papst
 */
class KimaiInstaller
{
    /**
     * @var array
     */
    protected $config = array();
    /**
     * @var string
     */
    protected $timezone = null;
    /**
     * @var string
     */
    protected $domain = null;
    /**
     * @var Callable
     */
    protected $logger = null;
    /**
     * @var string
     */
    protected $baseDir = null;

    /**
     * KimaiInstaller constructor.
     *
     * @param string $baseUrl
     * @param array $config
     */
    public function __construct($baseUrl = null, $config = null)
    {
        if (!empty($baseUrl)) {
            $this->setBaseUrl($baseUrl);
        }
        if (!empty($config)) {
            $this->config = $config;
        }
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl($url)
    {
        $this->domain = $url;
        return $this;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param string $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->baseDir = $basePath;
        return $this;
    }

    /**
     * @param Callable $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getFileName($name = null)
    {
        $baseDir = realpath($this->baseDir) . '/';

        if (!empty($name)) {
            return $baseDir . $name;
        }

        return $baseDir;
    }

    /**
     * @throws Exception
     */
    protected function validate()
    {
        if (empty($this->domain) || $this->domain == 'http://www.example.com') {
            $this->fail('Invalid Base URL. Supply one that is reachable by this machine. Currently: ' . $this->domain);
        }

        if (empty($this->baseDir)) {
            $this->fail('You must supply a base directory to your Kimai installation');
        }

        if (
            !is_dir($this->getFileName()) ||
            !is_dir($this->getFileName('installer/')) ||
            !is_dir($this->getFileName('temporary/')) ||
            !is_dir($this->getFileName('includes/'))
        ) {
            $this->fail('Base directory must be set and point to a Kimai installation. Currently: ' . $this->getFileName());
        }

        if (!is_array($this->config)) {
            $this->fail('Config cannot be empty and must be an array');
        }

        $required = array(
            'server_hostname',
            'server_database',
            'server_username',
            'server_password',
            'server_prefix',
            'language',
            'password_salt'
        );

        foreach ($required as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                $this->fail('Config key "' . $key . '" must be set and cannot be empty');
            }
        }
    }

    /**
     * @param string $message
     * @throws Exception
     */
    protected function fail($message)
    {
        throw new Exception($message);
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        if ($this->logger !== null && is_callable($this->logger)) {
            call_user_func($this->logger, $message);
        }
    }

    /**
     * Call the installer script via CURL
     *
     * @param $url
     * @param $params
     * @return mixed
     */
    protected function callInstaller($url, $params)
    {
        $kimaiUrl = $this->domain . $url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $kimaiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $this->log($output);
    }

    /**
     * @return $this
     */
    protected function install_setFilePermission()
    {
        $this->log('Setting file permissions');

        passthru('chmod -R 777 ' . $this->getFileName('temporary/'));
        passthru('chmod -R 777 ' . $this->getFileName('includes/'));

        return $this;
    }

    /**
     * ATTENTION, THIS IS DANGEROUS:
     * This command will drop ALL tables within the configured database, make sure you don't have any other stuff in there!
     *
     * @return $this
     */
    protected function install_resetDatabase()
    {
        $this->log('Resetting database');

        $user = $this->config['server_username'];
        $password = $this->config['server_password'];
        $database = $this->config['server_database'];
        $hostname = $this->config['server_hostname'];
        $prefix = $this->config['server_prefix'];

        // GAWK is not a default command, so we use another command
        /*
        $sql = 'mysql --user=' . $user .
                    ' --password=' . $password .
                    ' --host=' . $hostname .
                    ' --database=' . $database .
                    ' -e "show tables" | grep -v Tables_in | grep -v "+" | gawk \'{print "drop table " $1 ";"}\' | ' .
                    'mysql --user=' . $user .
                         ' --password=' . $password .
                         ' --host=' . $hostname .
                         ' --database=' . $database;
        */
        
        $sql = 'mysqldump --user=' . $user .
                        ' --password=' . $password .
                        ' --host=' . $hostname .
                        ' --databases ' . $database .
                        ' --add-drop-table --no-data | grep ^DROP | grep ' . $prefix .
                    ' | mysql --user=' . $user .
                            ' --password=' . $password .
                            ' --host=' . $hostname .
                            ' --database=' . $database;

        passthru($sql);

        return $this;
    }

    /**
     * @return $this
     */
    protected function install_setupDatabase()
    {
        $this->log('Installing database');

        $host = $this->config['server_hostname'];
        $user = $this->config['server_username'];
        $password = $this->config['server_password'];
        $database = $this->config['server_database'];
        $language = $this->config['language'];
        $prefix = $this->config['server_prefix'];

        $this->callInstaller(
            '/installer/processor.php',
            array(
                'axAction' => 'write_config',
                'hostname' => $host,
                'username' => $user,
                'password' => $password,
                'lang'     => $language,
                'prefix'   => $prefix,
                'database' => $database
            )
        );
        
        return $this;
    }

    /**
     * @return $this
     */
    protected function install_writeConfiguration()
    {
        $this->log('Configuring KIMAI');

        $this->callInstaller(
            '/installer/install.php',
            array(
                'accept'   => 1,
                'timezone' => $this->timezone
            )
        );
        
        return $this;
    }

    /**
     * Performs the installation.
     * Will validate internal values, provided through the setter upfront.
     */
    public function execute()
    {
        $this->validate();

        $this->log('Kimai installer v0.2 by Kevin Papst');

        $this->install_setFilePermission()
            ->install_resetDatabase()
            ->install_setupDatabase()
            ->install_writeConfiguration();

        $this->log('Successful installation! You can now track your times at: ' . $this->domain);
    }
}