<?php
/**
 * @author Omar A.Ajmi
 * @email devdevoops@gmail.com
 * @create date 2018-04-14 19:20:27
 * @modify date 2018-04-16 04:27:44
 * @desc [description]
 */
namespace App\Src\Libs;

use App\Src\Libs\DatabaseConnection;

class DsnGenerator
{
    /**
     * generates Sql Server DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getSqlsrvDNS(DatabaseConnection $conn)
    {
        $dsn = 'sqlsrv:server=';
        if (isset($conn->getConfigurations()['host']))
            $dsn .= $conn->getConfigurations()['host'];
        if (isset($conn->getConfigurations()['port']) && !empty($conn->getConfigurations()['port']))
            $dsn .= ',' . $conn->getConfigurations()['port'];
        if (isset($conn->getConfigurations()['dbname']))
            $dsn .= ';Database=' . $conn->getConfigurations()['dbname'];
        if (isset($conn->getConfigurations()['MultipleActiveResultSets']))
            $dsn .= '; MultipleActiveResultSets=' . ($conn->getConfigurations()['MultipleActiveResultSets'] ? 'true' : 'false');
        return $dsn;
    }

    /**
     * generates Dblib DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getDblibDNS(DatabaseConnection $conn)
    {
        $dsn = 'dblib:host=';
        if (isset($conn->getConfigurations()['host']))
            $dsn .= $conn->getConfigurations()['host'];
        if (isset($conn->getConfigurations()['port']) && !empty($conn->getConfigurations()['port']))
            $dsn .= ':' . $conn->getConfigurations()['port'];
        if (isset($conn->getConfigurations()['dbname']))
            $dsn .= ';dbname=' . $conn->getConfigurations()['dbname'];
        return $dsn;
    }

    /**
     * generates Sqlite DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getSqliteDNS(DatabaseConnection $conn)
    {
        $dsn = 'sqlite:';
        if (isset($conn->getConfigurations()['path'])) $dsn .= $conn->getConfigurations()['path'];
        elseif (isset($conn->getConfigurations()['memory']))
            $dsn .= ':memory:';
        return $dsn;
    }

    /**
     * generates Postgres DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getPgsqlDNS(DatabaseConnection $conn)
    {
        $dsn = 'pgsql:';
        if (isset($conn->getConfigurations()['host']) && !empty($conn->getConfigurations()['host']))
            $dsn .= 'host=' . $conn->getConfigurations()['host'] . ' ';
        if (isset($conn->getConfigurations()['port']) && !empty($conn->getConfigurations()['port']))
            $dsn .= 'port=' . $conn->getConfigurations()['port'] . ' ';
        if (isset($conn->getConfigurations()['dbname']))
            $dsn .= 'dbname=' . $conn->getConfigurations()['dbname'] . ' ';
        else
                    // Used for temporary connections to allow operations like dropping the database currently connected to.
                    // Connecting without an explicit database does not work, therefore "template1" database is used
                    // as it is certainly present in every server setup.
        $dsn .= 'dbname=template1' . ' ';
        if (isset($conn->getConfigurations()['sslmode']))
            $dsn .= 'sslmode=' . $conn->getConfigurations()['sslmode'] . ' ';
        return $dsn;
    }

    /**
     * generates Oracle DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getOracleDNS(DatabaseConnection $conn)
    {
        //
    }

    /**
     * generates IBM DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getIbmDNS(DatabaseConnection $conn)
    {
        $dsn = 'ibm:DRIVER={IBM DB2 ODBC DRIVER};';
        if (isset($conn->getConfigurations()['host']))
            $dsn .= 'HOSTNAME=' . $conn->getConfigurations()['host'] . ';';
        if (isset($conn->getConfigurations()['port']))
            $dsn .= 'PORT=' . $conn->getConfigurations()['port'] . ';';
        $dsn .= 'PROTOCOL=TCPIP;';
        if (isset($conn->getConfigurations()['dbname']))
            $dsn .= 'DATABASE=' . $conn->getConfigurations()['dbname'] . ';';
        return $dsn;
    }

    /**
     * generates Mysql DSN
     *
     * @param DatabaseConnection $conn
     * @return string
     */
    public function getMysqlDNS(DatabaseConnection $conn)
    {
        $dsn = 'mysql:';
        if (isset($conn->getConfigurations()['host']) and !empty($conn->getConfigurations()['host']))
            $dsn .= 'host=' . $conn->getConfigurations()['host'] . ';';
        if (isset($conn->getConfigurations()['port']))
            $dsn .= 'port=' . $conn->getConfigurations()['port'] . ';';
        if (isset($conn->getConfigurations()['dbname']))
            $dsn .= 'dbname=' . $conn->getConfigurations()['dbname'] . ';';
        if (isset($conn->getConfigurations()['unix_socket']))
            $dsn .= 'unix_socket=' . $conn->getConfigurations()['unix_socket'] . ';';
        if (isset($conn->getConfigurations()['charset']))
            $dsn .= 'charset=' . $conn->getConfigurations()['charset'] . ';';
        return $dsn;
    }
}
