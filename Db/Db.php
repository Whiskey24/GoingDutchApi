<?php
class Db{
    // http://www.phpro.org/tutorials/Introduction-to-PHP-PDO.html#4

    /*** Declare instance ***/
    private static $instance = NULL;

    /**
     *
     * the constructor is set to private so
     * so nobody can create a new instance using new
     *
     */
    private function __construct() {
        /*** maybe set the db name here later ***/
    }

    /**
     *
     * Return DB instance or create initial connection
     *
     * @return object (PDO)
     *
     * @access public
     *
     */
    public static function getInstance() {

        if (!self::$instance)
        {
            $config = parse_ini_file('dbconfig.ini');
            $a="mysql:host={$config['host']};dbname={$config['dbname']}";
            self::$instance = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['user'], $config['pass']);
            self::$instance-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }


    /**
     *
     * Like the constructor, we make __clone private
     * so nobody can clone the instance
     *
     */
    private function __clone(){
    }

} /*** end of class ***/

/*
try    {
    // query the database
    $result = DB::getInstance()->query("SELECT * FROM animals");

    // loop over the results
    foreach($result as $row)
    {
        print $row['animal_type'] .' - '. $row['animal_name'] . '<br />';
    }
}
catch(PDOException $e)
{
    echo $e->getMessage();
}*/
