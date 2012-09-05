<?php

/**
 * When setting the `id` column, consider the length of the prefix you're specifying in $this->prefix
 *
 *
 CREATE TABLE `details` (
 `id` char(17) NOT NULL,
 `url` varchar(255) default NULL,
 `c_url` varchar(255) default NULL,
 `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
 `server name` varchar(64) default NULL,
 `perfdata` MEDIUMBLOB,
 `type` tinyint(4) default NULL,
 `cookie` BLOB,
 `post` BLOB,
 `get` BLOB,
 `pmu` int(11) default NULL,
 `wt` int(11) default NULL,
 `cpu` int(11) default NULL,
 `server_id` char(3) NOT NULL default 't11',
 `aggregateCalls_include` varchar(255) DEFAULT NULL,
 PRIMARY KEY  (`id`)
 );

 */

require_once XHPROF_LIB_ROOT.'/utils/Db/Pdo.php';

function from_unixtime_sqlite ($time) {
    return date('Y-m-d H:i:s');
}

class Db_Sqlite extends Db_Pdo
{
    protected $curStmt;
    
    public function connect()
    {
        $connectionString = 'sqlite:' . $this->config['dbname'];
        $db = new PDO($connectionString);
        if ($db === FALSE)
        {
            xhprof_error("Could not connect to db");
            $run_desc = "could not connect to db";
            throw new Exception("Unable to connect to database");
            return false;
        }
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
//sqlite offers insta-create awesomeness
$this->db->exec("CREATE TABLE IF NOT EXISTS `details` (
 `id` char(17) NOT NULL,
 `url` varchar(255) default NULL,
 `c_url` varchar(255) default NULL,
 `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
 `server name` varchar(64) default NULL,
 `perfdata` MEDIUMBLOB,
 `type` tinyint(4) default NULL,
 `cookie` BLOB,
 `post` BLOB,
 `get` BLOB,
 `pmu` int(11) default NULL,
 `wt` int(11) default NULL,
 `cpu` int(11) default NULL,
 `server_id` char(3) NOT NULL default 't11',
 `aggregateCalls_include` varchar(255) DEFAULT NULL,
 PRIMARY KEY  (`id`)
 );
CREATE INDEX IF NOT EXISTS main.url ON details (url ASC);
CREATE INDEX IF NOT EXISTS main.c_url ON details (c_url ASC);
CREATE INDEX IF NOT EXISTS main.cpu ON details (cpu ASC);
CREATE INDEX IF NOT EXISTS main.wt ON details (wt ASC);
CREATE INDEX IF NOT EXISTS main.pmu ON details (pmu ASC);
CREATE INDEX IF NOT EXISTS main.timestamp ON details (timestamp ASC);
");
	$this->db->sqliteCreateFunction("COMPRESS", "gzcompress", 1);
	$this->db->sqliteCreateFunction("UNCOMPRESS", "gzuncompress", 1);
        $this->db->sqliteCreateFunction("FROM_UNIXTIME", "from_unixtime_sqlite", 1);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    
    public function query($sql)
    {
        $this->curStmt = $this->db->query($sql);
        return $this->curStmt;
    }
    
    public static function getNextAssoc($resultSet)
    {
        return $resultSet->fetch();
    }
    
    public function escape($str)
    {
	$str = $this->db->quote($str);
        //Dirty trick, PDO::quote add quote around values (you're beautiful => 'you\'re beautiful')
        // which are already added in xhprof_runs.php
        $str = substr($str, 0, -1);
        $str = substr($str, 1);
        return $str;
    }
    
    public function affectedRows()
    {
        if ($this->curStmt === false) {
            return 0;
        }
        return $this->curStmt->rowCount();
    }
    
    public static function unixTimestamp($field)
    {
        return "strftime('%s',".$field.")";
    }
    
    public static function dateSub($days)
    {
        return "datetime('now', '-{$days} days')";
        return 'DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)';
    }
}
