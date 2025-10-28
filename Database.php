<?php

// Database compatibility shim — the project has been migrated to zero-DB mode.
// The original Database.php has been replaced with this stub to avoid accidental
// use. If your code still instantiates Database, update it to use the file-backed
// Auth/Ticket implementations. This class will throw an informative exception.

class Database {
    public function __construct() {
        throw new RuntimeException("Database class is deprecated in zero-DB mode. Use file-backed storage (users.json/ticket.json) and remove Database usage.");
    }
}
