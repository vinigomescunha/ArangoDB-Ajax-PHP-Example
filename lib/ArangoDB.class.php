<?php
namespace triagens\ArangoDb;
require dirname(__FILE__)  . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR . 'config.php';

require dirname(__FILE__)  . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

Class ArangoDBMan extends Document
{

    private $connectionOptions = [];
    public $connection;
    public $collection;
    public $collectionHandler;
    public $documentHandler;
    public $post = [];
    public $page = 0;
    public $limit;
    public $sort = '';

    function __construct()
    {
        $op = [ 
        ConnectionOptions::OPTION_ENDPOINT => TCP,
        ConnectionOptions::OPTION_AUTH_TYPE => AUTH,
        ConnectionOptions::OPTION_AUTH_USER => USER,
        ConnectionOptions::OPTION_AUTH_PASSWD => PASSWD,
        ConnectionOptions::OPTION_CONNECTION => C_TYPE,
        ConnectionOptions::OPTION_TIMEOUT => 5,
        ConnectionOptions::OPTION_RECONNECT => true
        ];
        $this->post = $_POST;
        if (isset($_GET['page'])) 
            $this->page = (intval($_GET['page']) * PER_PAGE);
	if(isset($_GET['sort'])) 
	    $this->sort = ' SORT u.' . $_GET['sort'];
        $this->limit = " LIMIT " . $this->page . " , " . PER_PAGE;
        $this->set("connectionOptions", $op);
        $this->ready();
    }

    public function set($option, $value)
    {
        $this->$option = $value;
    }

    public function get($value)
    {
        return $this->$value;
    }

    public function ready()
    {
        /* create new Connection create and load Collection */
        $this->connection = new Connection($this->connectionOptions);
        $this->collection = new Collection(COLLECTION_NAME);
        /* call Collections handler - lib client */
        $this->collectionHandler = new CollectionHandler($this->connection);
        /* if collection called not exists then create */
        if (!$this->collectionHandler->has(COLLECTION_NAME)) $this->collectionHandler->create($this->collection); //collectionHandler return collection id
        $this->documentHandler = new DocumentHandler($this->connection);
    }

    public function create()
    {
        $d = new Document();
        /* loop variables and set to Document */
        foreach($this->post as $key => $value) 
            $d->set($key, $value);
        /* save document in collection */
        $id = $this->documentHandler->save(COLLECTION_NAME, $d);
        echo json_encode(["result" => $id]);
    }

    public function find()
    {
        $f = [];
        /* get all fields and build a sentence to AQL find with params */
        if (!empty($this->post))
            foreach($this->post as $k => $v) 
                $f[] = "u.$k == @$k";
            $filter = !empty($f) ? ' filter ' . implode($f, ' && ') : '';
            $query = "FOR u IN " . COLLECTION_NAME . $filter . $this->limit . $this->sort . " RETURN u";
            $statement = new Statement($this->connection, [ "query" => $query, "count" => true, "bindVars" => $this->post ]);
            $cursor = $statement->execute();
	    $total = $this->collectionHandler->count(COLLECTION_NAME);
            $result = [ 'count' => $cursor->getCount(), 'total' => $total, 'pages' => ($total > 0 ? $total / PER_PAGE : $total) ];
            /* transform in array  */
            foreach($cursor->getAll() as $key => $value)
                if (is_object($cursor->getAll() [$key])) 
                    $result["result"][$key] = get_object_vars($cursor->getAll() [$key]);
                echo json_encode($result);
            }

            public function update()
            {
                $result = false;
                $key = filter_input(INPUT_GET, '_key', FILTER_SANITIZE_SPECIAL_CHARS);
                /* get this document from the server by id */
                if(!$this->documentHandler->has(COLLECTION_NAME, $key)) 
                    return false;
                $dh = $this->documentHandler->getById(COLLECTION_NAME, $key);
                /* update this document*/
                foreach($this->post as $key => $value) 
                    $dh->set($key, $value);
                $result = $this->documentHandler->update($dh);
                echo json_encode(['result' => $result]);
            }

            public function delete()
            {
                $result = false;
                $key = filter_input(INPUT_GET, '_key', FILTER_SANITIZE_SPECIAL_CHARS);
                if($this->documentHandler->has(COLLECTION_NAME, $key)) 
                    $result = $this->documentHandler->removeById(COLLECTION_NAME, $key);
                echo json_encode(['result' => $result]);
            }
        }
