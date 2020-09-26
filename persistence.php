<?php
/*
 * Database table description for events and categories
 */


function evtcal_tableName_events() {
    global $wpdb;
    return $wpdb->prefix . 'evtcal';
}

function evtcal_tableName_categories() {
    global $wpdb;
    return $wpdb->prefix . 'evtcal_cats';
}

function evtcal_tableName_eventsVsCats() {
    global $wpdb;
    return $wpdb->prefix . 'evtcal_evt_vs_cats';
}


function evtcal_initTables() {
    global $wpdb;
    $wpdb->show_errors();
    $eventsTableName = evtcal_tableName_events();
    $categoriesTableName = evtcal_tableName_categories();
    $eventsVsCatsTableName = evtcal_tableName_eventsVsCats();
    $charset_collate = $wpdb->get_charset_collate();

    // Events
    $sql = "CREATE TABLE $eventsTableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        eventId tinytext NOT NULL,
        date date DEFAULT '0000-00-00' NOT NULL,
        time time DEFAULT '00:00:00' NOT NULL,
        dateEnd date DEFAULT '0000-00-00' NOT NULL,
        timeEnd time DEFAULT '00:00:00' NOT NULL,
        title tinytext NOT NULL,
        organizer tinytext DEFAULT '' NOT NULL,
        location tinytext DEFAULT '' NOT NULL,
        description text NOT NULL,
        url varchar(1300) DEFAULT '' NOT NULL,
        public tinyint(2) DEFAULT 0 NOT NULL,
        created timestamp NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
    dbDelta( $sql );

    // Categories
    $sql = "CREATE TABLE $categoriesTableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        categoryId tinytext NOT NULL,
        name tinytext NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
    dbDelta( $sql );

    // Events vs. Categories (many-to-many relationship)
    $sql = "CREATE TABLE $eventsVsCatsTableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        category_id mediumint(9) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";
    dbDelta( $sql );
}

function evtcal_deleteTables() {
    global $wpdb;
    $wpdb->show_errors();

    foreach ([
        evtcal_tableName_eventsVsCats(),
        evtcal_tableName_events(),
        evtcal_tableName_categories(),
    ] as $tableName) {
        $wpdb->query("DROP TABLE $tableName;");
    }

}

function evtcal_whereAnd($conditions) {
    if (empty($conditions)) {
        return '';
    }
    return 'WHERE ' . implode(' AND ', $conditions);
}


abstract class evtcal_DbTable {
    var $data = null;
    const IDPREFIX = 'x:';
    const DEFAULTS = array();
    abstract static function getTableName();
    static function queryRow($sql) {
        global $wpdb;
        $sql = str_replace('[T]', static::getTableName(), $sql);
        $rows = $wpdb->get_results($sql);
        if (empty($rows)) {
            return null;
        }
        return $rows[0];
    }

    abstract static function getAllFieldNames();
    abstract static function getIdFieldName();

    function __construct($data) {
        if (is_array($data)) {
            $this->data = (object) $data;
        } else {
            $this->data = $data;
        }
    }

    function exists() {
        global $wpdb;
        $idFieldName = $this->getIdFieldName();
        $tableName = $this->getTableName();
        $row = $wpdb->get_row("SELECT $idFieldName FROM $tableName WHERE $idFieldName='{$this->getField($idFieldName)}';");
        return !empty($row);
    }

    function store() {
        global $wpdb;
        $tableName = $this->getTableName();
        if ($this->exists()) {
            $where = array($this->getIdFieldName() => $this->getField($this->getIdFieldName()));
            $affectedRows = $wpdb->update(
                $tableName,
                $this->getFullData(),
                $where
            );
        } else {
            $affectedRows = $wpdb->insert($tableName, $this->getFullData());
        }
        $e = $wpdb->last_error;
        return $affectedRows;
    }

    function getField($name, $default=null) {
        if (strcmp($name, $this->getIdFieldName()) === 0) {
            $this->initId();
        }
        if (isset($this->data->$name)) {
            return $this->data->$name;
        }
        return $this->getDefault($name, $default);
    }

    private function initId() {
        $idFieldName = $this->getIdFieldName();
        if (!isset($this->data->$idFieldName) || strcmp($this->data->$idFieldName, '') === 0) {
            $this->data->$idFieldName = uniqid(self::IDPREFIX);
        }
    }

    function getFullData() {
        $data = array();
        foreach ($this->getAllFieldNames() as $fieldName) {
            $data[$fieldName] = $this->getField($fieldName);
        }
        return $data;
    }

    function getDefault($name, $default=null) {
        if ($default != null) {
            return $default;
        }
        if (isset(self::DEFAULTS[$name])) {
            return self::DEFAULTS[$name];
        }
        return '';
    }
}