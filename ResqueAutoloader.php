<?php
namespace resque\lib;
use \yii\BaseYii;

/**
 * This file part of RResque
 *
 * Autoloader for Resque library
 *
 * For license and full copyright information please see main package file
 * @package       yii2-resque
 */

class ResqueAutoloader
{
    /**
     * Registers Raven_Autoloader as an SPL autoloader.
     */
    static public function register()
    {
        spl_autoload_unregister(['Yii', 'autoload']);
        spl_autoload_register([new self,'autoload']);
        spl_autoload_register(['Yii', 'autoload'], true, true);
        
    }

    /**
     * Handles autoloading of classes.
     *
     * @param  string  $class  A class name.
     *
     * @return boolean Returns true if the class has been loaded
     */
    static public function autoload($class)
    {
        //yii 2 advance;
        if(file_exists(\yii\BaseYii::$app->basePath.'/../frontend/components')){
            $file= \yii\BaseYii::$app->basePath.'/../frontend/components';
            if(scandir($file)){
            foreach (scandir($file) as $filename) {
               $path = $file. $filename;
                if (is_file($path)) {

                    require_once $path;
                }
            }
        }
    }
     // yii 2 basic
    else{
        $file= \Yii::getAlias('@app').'/components/';
       foreach (scandir($file) as $filename) {
            $path = $file. $filename;
             if (is_file($path)) {
                
                 require_once $path;
             }
         }
    }

       
        require_once(dirname(__FILE__) . '/lib/Resque/Job.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Event.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Redis.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Worker.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Stat.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Job/Status.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Exception.php');
        require_once(dirname(__FILE__) . '/lib/MonologInit/MonologInit.php');
        
    }
}