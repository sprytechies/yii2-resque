Yii2 Resque
===========
Yii2 Resque

Installation
------------

1.  The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

    Either run

    ```
    php composer.phar require --prefer-dist resque/yii2-resque "*"
    ```

    or add
    
    ```
    "resque/yii2-resque": "*"
    ```

    to the require section of your `composer.json` file.

2.  Create a file named `ResqueController.php` in app/commands folder and write the following code in it.
    ```php
        namespace app\commands;
        use Yii;
        use yii\console\Controller;
        use resque\lib\ResqueAutoloader;
        use resque\lib\Resque\Resque_Worker;
        /**
         *
         * @author Sprytechies
         * @since 2.0
         */
        class ResqueController extends Controller
        {
            /**
             * This command echoes what you have entered as the message.
             * @param string $message the message to be echoed.
             */
            public function actionIndex()
            {

                    $includeFiles=getenv('INCLUDE_FILES');
                    if ($includeFiles) {
                        $includeFiles = explode(',', $includeFiles);
                        foreach ($includeFiles as $file) {
                            require_once $file;
                        }
                    }
                    $config = require(Yii::getAlias('@app') . '/config/console.php');
                    $application = new \yii\console\Application($config);

                    # Turn off our amazing library autoload
                    spl_autoload_unregister(array('Yii','autoload'));

                    require_once(Yii::getAlias('@vendor') . '/resque/yii2-resque/ResqueAutoloader.php');
                    ResqueAutoloader::register();

                    # Give back the power to Yii
                    spl_autoload_register(array('Yii','autoload'));

                    $QUEUE = getenv('QUEUE');
                    if(empty($QUEUE)) {
                        die("Set QUEUE env var containing the list of queues to work.\n");
                    }

                    $REDIS_BACKEND = getenv('REDIS_BACKEND');
                    $REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
                    $REDIS_AUTH = getenv('REDIS_AUTH');

                    if(!empty($REDIS_BACKEND)) {
                        $REDIS_BACKEND_DB = (!empty($REDIS_BACKEND_DB)) ? $REDIS_BACKEND_DB : 0;
                        Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB, $REDIS_AUTH);
                    }

                    $logLevel = 0;
                    $LOGGING = getenv('LOGGING');
                    $VERBOSE = getenv('VERBOSE');
                    $VVERBOSE = getenv('VVERBOSE');
                    if(!empty($LOGGING) || !empty($VERBOSE)) {
                        $logLevel = Resque_Worker::LOG_NORMAL;
                    } else if(!empty($VVERBOSE)) {
                        $logLevel = Resque_Worker::LOG_VERBOSE;
                    }

                    $logger = null;
                    $LOG_HANDLER = getenv('LOGHANDLER');
                    $LOG_HANDLER_TARGET = getenv('LOGHANDLERTARGET');

                    if (class_exists('MonologInit_MonologInit')) {
                        if (!empty($LOG_HANDLER) && !empty($LOG_HANDLER_TARGET)) {
                            $logger = new MonologInit_MonologInit($LOG_HANDLER, $LOG_HANDLER_TARGET);
                        } else {
                            fwrite(STDOUT, '*** loghandler or logtarget is not set.'."\n");    
                        }
                    } else {
                        fwrite(STDOUT, '*** MonologInit_MonologInit logger cannot be found, continue without loghandler.'."\n");
                    }

                    $interval = 5;
                    $INTERVAL = getenv('INTERVAL');
                    if(!empty($INTERVAL)) {
                        $interval = $INTERVAL;
                    }

                    $count = 1;
                    $COUNT = getenv('COUNT');
                    if(!empty($COUNT) && $COUNT > 1) {
                        $count = $COUNT;
                    }

                    $PREFIX = getenv('PREFIX');
                    if(!empty($PREFIX)) {
                        fwrite(STDOUT, '*** Prefix set to '.$PREFIX."\n");
                        Resque::redis()->prefix($PREFIX);
                    }

                    if($count > 1) {
                        for($i = 0; $i < $count; ++$i) {
                            $pid = Resque::fork();
                            if($pid == -1) {
                                die("Could not fork worker ".$i."\n");
                            }
                            // Child, start the worker
                            else if(!$pid) {
                                startWorker($QUEUE, $logLevel, $logger, $interval);
                                break;
                            }
                        }
                    }
                    // Start a single worker
                    else {
                        $PIDFILE = getenv('PIDFILE');
                        if ($PIDFILE) {
                            file_put_contents($PIDFILE, getmypid()) or
                                die('Could not write PID information to ' . $PIDFILE);
                        }

                        $this->startWorker($QUEUE, $logLevel, $logger, $interval);
                    }
            }

            function startWorker($QUEUE, $logLevel, $logger, $interval)
            {
                $queues = explode(',', $QUEUE);
                $worker = new Resque_Worker($queues);

                if (!empty($logger)) {
                    $worker->registerLogger($logger);    
                } else {
                    fwrite(STDOUT, '*** Starting worker '.$worker."\n");
                }

                $worker->logLevel = $logLevel;
                $worker->work($interval);
            }
        }

    ```
    
3.  Add these in you config/web.php
    ```php
    'components' => [
        ...
        'resque' => [ 
            'class' => '\resque\RResque', 
            'server' => 'localhost',     // Redis server address
            'port' => '6379',            // Redis server port
            'database' => 0,             // Redis database number
            'password' => '',            // Redis password auth, set to '' or null when no auth needed
        ], 
        ...
    ]
    ```
4.  Make sure you have already installed `Yii2-redis` Extension


Usage
-----

Once the extension is installed,  :

1.  Create a folder `components` in your app. 
    You can put all your class files into this `components` folder.

    Example - 

    ```php
    namespace app\components;
    class Worker_ClassWorker
    {
        public function setUp()
        {
            # Set up environment for this job
        }

        public function perform()
        {

            echo "Hello World";
            # Run task
        }

        public function tearDown()
        {
            # Remove environment for this job
        }
    }
    ```
2.  Write this command to start resque in your console/terminal.
    QUEUE=queue1 php yii resque start
