<?php
require_once('autoload.php');

$version = 0;
$updates = [];

// Utility functions
function pre_echo(...$texts) : void {
    echo "<pre style='font-size: 1.5em; margin: 0.125em;'>";
    echo implode("\n",$texts)."\n";
    echo "</pre>";
}
function pre_die(...$texts) : void {
    echo "<pre style='font-size: 1.5em; margin: 0.125em; color:#ff0000; background-color:#000;'>";
    echo implode("\n",$texts);
    echo "</pre>";
    die();
}
function ellipsis($string, $length) : string {
    return substr($string, 0, $length).(strlen($string)>$length ? '...' : '');
}
function nth($num) : string {
    if (($num>=11) && ($num<=13)) { return 'th'; }
    if ($num % 10 == 1) { return "st"; }
    elseif ($num % 10 == 2) { return "nd"; }
    elseif ($num % 10 == 3) { return "rd"; }
    else { return 'th'; }
}

// Main update process
if (file_exists('sql/db-updates.sql')) {
    pre_echo("Checking for dbupdates table.");
    // Make sure we have a versions table to work with
    $justCreated = DbUpdate::ensureTableExists();
    if ($justCreated) {
        $checkAgain = DbUpdate::ensureTableExists();
        if ($checkAgain) {
            // Tried to create it again - it's not working
            pre_die("Table `dbupdates` does not exist and unable to create it.");
        } else {
            pre_echo("Table `dbupdates` created.");
        }
    }

    pre_echo("Checking for update file.");
    // Start parsing file into update chunks
    $all_sql = trim(file_get_contents('sql/db-updates.sql'));
    $sql_parts = explode('/* UPDATE */', $all_sql);
    $i = 0;
    $max_version = 0;
    // Validate chunk by chunk, building array of [version]=>[SQL]
    pre_echo("Parsing update file.");
    foreach ($sql_parts as $part) {
        $part = trim($part);
        if (empty($part)) { continue; } // No point processing an empty update
        $version_matches = [];
        $version_find = preg_match('/^\/\*\s*VERSION\s*(\d+)\s*\*\//i', $part, $version_matches);
        if ($version_find === false) {
            // Regex problem
            pre_die("Error in regular expression - please review and retry");
        }
        if ($version_find === 0) {
            // This update doesn't have a version comment
            $suffix = nth($i);
            pre_die("Bad SQL update file - {$i}{$suffix} block doesn't have a version-number comment.",
                    "SQL reads ".ellipsis($part, 100));
        }
        $this_version = ((int)$version_matches[1]);
        if (array_key_exists($this_version,$updates)) {
            // Can't have duplicate version entries
            pre_die("Bad SQL update file - duplicate entry for version #{$this_version}.",
                    "SQL reads ".ellipsis($part, 100));
        }
        if ($this_version < $max_version) {
            // Versions MUST increment upwards
            pre_die("Bad SQL update file - version #{$this_version} appears after version #{$max_version}.",
                    "SQL reads ".ellipsis($part, 100));
        }
        // All OK
        $max_version = $this_version; // It must be the largest - no need to test
        $updates[$this_version] = $part;
        $i++;
    }

    // Check if we're already up to date
    $current_version = DbUpdate::highestVersion();
    if ($max_version === $current_version) {
        pre_die("Your database is up-to-date.");
    }
    // Check if database is "more up-to-date" than file
    if ($current_version > $max_version) {
        pre_die("Bad SQL update file - max version is {$max_version} but database is already at version {$current_version}.");
    }
    
    pre_echo("Database is current at version {$current_version}.","Target version is {$max_version}.");

    // Now sort by key to ensure they're in version order
    ksort($updates, SORT_NUMERIC);

    // Now apply any updates we haven't already tried
    $pdo = db::getPDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach (array_keys($updates) as $v) {
        if ($v <= $current_version) { continue; }
        $sql = $updates[$v];
        if (!$pdo->beginTransaction()) {
            pre_die("Unable to start a transaction at version #{$v}.");
        }
        try {
        $pdo->exec($sql);
        } catch (Exception $e) {
            pre_die("Error running SQL for version #{$v}.",
                    "You will need to check that the database is in a valid state.",
                    "SQL reads ".ellipsis($sql, 1000));
        }
        $sql = "INSERT INTO dbupdates (`version`,`created`,`modified`) VALUES ({$v},NOW(),NOW());";
        $pdo->exec($sql);
        if ($pdo->inTransaction()) {
            if (!$pdo->commit()) {
                pre_die("Unable to commit transaction for version #{$v}.",
                        "SQL reads ".ellipsis($sql, 1000));
            }
        } else {
            // That's OK - if we performed CREATE/ ALTER statements, they sometimes auto-commit apparently
        }
        pre_echo("Upgraded database to version {$v}.");
    }
}

$final_version = DbUpdate::highestVersion();
pre_echo("Upgrade complete.", "Database now at version {$final_version}.");
die();