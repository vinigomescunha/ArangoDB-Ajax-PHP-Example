<?php
namespace triagens\ArangoDb;
require_once "../lib/ArangoDB.class.php";
$e = new ArangoDBMan();
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
switch ($action) {
    case "find":
        $e->find();/* RECEIVE GET page - optional and POST fields  - optional*/
        break;
    case "create":
        $e->create();/*RECEIVE POST FIELDS*/
        break;
    case "update":
        $e->update();/* RECEIVE GET _key to Update and POST any data to modify */
        break;
    case "delete":
        $e->delete();/* * RECEIVE GET _key */
        break;
}
