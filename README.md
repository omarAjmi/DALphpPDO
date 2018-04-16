# DALphpPDO Version 1.0

**DALphpPDO** is a **php** library that allows you to connect, query and obtain results from all major relational dbms.

## List of supported **RDBMS**:

 - [Mysql](https://www.mysql.com/) (default)
   [PostgresSQL](https://postgresql.org/).
   [Oracle](https://www.oracle.com/database)
   [SqlServer](https://www.microsoft.com/en-us/sql-server)
   [SqlLite](https://sqlite.org/)
   [Ibm](https://www.ibm.com/db2/)
   [Sybase](http://www.sybase.com/)
   [Odbc](https://docs.microsoft.com/en-us/sql/odbc/microsoft-open-database-connectivity-odbc)

## Loading Database connection configurations:

configurations residesin the **/Settings.php** file.
**Example of Mysql configs:**

    'mysql'  =>  [  //the default pool (driver)
	    'driver'  =>  'mysql',
	    'host'  =>  'localhost',
	    'dbname'  =>  'sys',
	    'user'  =>  'root',
	    'password'  =>  'toor',
	    'prefix'  =>  'DB1_',
	    'port'  =>  3306,
	    'persistent'  =>  1,
	    'fetchmode'  =>  'object',
	    'prepare'  =>  1
    ],
configurations are devised as pools, each pool hase the correspondant RDBMS driver name as a global name for the pool like the exemple above.
loading these configurations is strait forward:

    $configs  =  new  DatabaseConfiguration();
    /**
	 * without parameters loads the default configurations
	 * for the default pool which is mysql
	 */
or:

    $configs  =  new  DatabaseConfiguration('pgsql');

or

    $configs  =  new  DatabaseConfiguration('odbc',  'path/to/settings/file');

## Creating a connection instance:

creating the connection instance requires a DatabaseConfiguration object that holds all the necessary parameters to open a connection throgh PHP's PDO.
creating a connection instance is strait forward:

    $dbConnect  =  new  DatabaseConnection($configs);

## Querying databases:

for database queries you need to instantiate the QueryBuilderBase which is the engin that generates SQL queries and execute them without the need to deal directly with SQL syntax.
all this object needs is the DatabaseConnection object that holds all the necessary infos for the database connection.

    $qb  =  new  QueryBuilderBase($dbConnect);

### creating queries:

creating queries is as simple as creating native SQL queries.
#### Select:

    $qb->select('column_name')->from('table_name');
    //selects a spesific column from the spesific table
or

    $qb->select(['column_name', 'column_name',...])->from('table_name');
    //selects multiple columns from the spesific table
   or

    $qb->select()->from('table_name');
    //selects all columns from the spesific table

#### Where:

    $qb->select('column_name')->from('table_name')->where("column = value");
#### and where:

    $qb->select('column_name')->from('table_name')->where("column = value")->andWhere("column = value");
#### or where:

    $qb->select('column_name')->from('table_name')->where("column = value")->orWhere("column = value");

#### groupBy

    $qb->select('column_name')->from('table_name')->where("column = value")->orWhere("column = value")->groupBy("column_name");

