<?php
namespace Src\Libs;

use Src\Libs\DatabaseConnection;

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
        if (isset($conn->configuration['host']))
            $dsn .= $conn->configuration['host'];
        if (isset($conn->configuration['port']) && !empty($conn->configuration['port']))
            $dsn .= ',' . $conn->configuration['port'];
        if (isset($conn->configuration['dbname']))
            $dsn .= ';Database=' . $conn->configuration['dbname'];
        if (isset($conn->configuration['MultipleActiveResultSets']))
            $dsn .= '; MultipleActiveResultSets=' . ($conn->configuration['MultipleActiveResultSets'] ? 'true' : 'false');
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
        if (isset($conn->configuration['host']))
            $dsn .= $conn->configuration['host'];
        if (isset($conn->configuration['port']) && !empty($conn->configuration['port']))
            $dsn .= ':' . $conn->configuration['port'];
        if (isset($conn->configuration['dbname']))
            $dsn .= ';dbname=' . $conn->configuration['dbname'];
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
        if (isset($conn->configuration['path'])) $dsn .= $conn->configuration['path'];
        elseif (isset($conn->configuration['memory']))
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
        if (isset($conn->configuration['host']) && !empty($conn->configuration['host']))
            $dsn .= 'host=' . $conn->configuration['host'] . ' ';
        if (isset($conn->configuration['port']) && !empty($conn->configuration['port']))
            $dsn .= 'port=' . $conn->configuration['port'] . ' ';
        if (isset($conn->configuration['dbname']))
            $dsn .= 'dbname=' . $conn->configuration['dbname'] . ' ';
        else
                    // Used for temporary connections to allow operations like dropping the database currently connected to.
                    // Connecting without an explicit database does not work, therefore "template1" database is used
                    // as it is certainly present in every server setup.
        $dsn .= 'dbname=template1' . ' ';
        if (isset($conn->configuration['sslmode']))
            $dsn .= 'sslmode=' . $conn->configuration['sslmode'] . ' ';
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
        if (isset($conn->configuration['host']))
            $dsn .= 'HOSTNAME=' . $conn->configuration['host'] . ';';
        if (isset($conn->configuration['port']))
            $dsn .= 'PORT=' . $conn->configuration['port'] . ';';
        $dsn .= 'PROTOCOL=TCPIP;';
        if (isset($conn->configuration['dbname']))
            $dsn .= 'DATABASE=' . $conn->configuration['dbname'] . ';';
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
        if (isset($conn->configuration['host']) && !empty($conn->configuration['host']))
            $dsn .= 'host=' . $conn->configuration['host'] . ';';
        if (isset($conn->configuration['port']))
            $dsn .= 'port=' . $conn->configuration['port'] . ';';
        if (isset($conn->configuration['dbname']))
            $dsn .= 'dbname=' . $conn->configuration['dbname'] . ';';
        if (isset($conn->configuration['unix_socket']))
            $dsn .= 'unix_socket=' . $conn->configuration['unix_socket'] . ';';
        if (isset($conn->configuration['charset']))
            $dsn .= 'charset=' . $conn->configuration['charset'] . ';';
        return $dsn;
    }
}
