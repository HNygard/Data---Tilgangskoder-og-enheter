<?php



if (!function_exists('getDirContents')) {
    function getDirContents($dir) {
        $command = 'find "' . $dir . '"';
        exec($command, $find);
        $data_store_files = array();
        foreach ($find as $line) {
            if (is_dir($line)) {
                // -> Find already got all recursively
                continue;
            }
            $data_store_files[] = $line;
        }
        return $data_store_files;
    }

    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        return $length === 0 || substr($haystack, -$length) === $needle;
    }
}

$files = getDirContents(__DIR__ . '/docs/');
foreach($files as $file) {
    if (str_ends_with($file, 'tilgangskoder.csv')) {
        readTilgangskoder_writeToJson($file);
    }
}

function readCsvFileBatch($file) {
    $file = fopen($file, 'r');
    $csv = fgetcsv($file);
    $headers = $csv;

    $objects = array();
    while ($csv = fgetcsv($file)) {
        $obj = new stdClass();
        foreach ($csv as $i => $value) {
            $obj->{$headers[$i]} = trim($value);
        }
        $objects[] = $obj;
    }
    return $objects;
}

class Tilgangskode {

    public $code;
    public $name_or_description;
    public $note;

    public $legal_basis_used_in_category;
    public $legal_basis_used_in_category_hint;

    public $system;
    public $codeSystemId;
    public $systemSortOrder;

    public $valid_from;
    public $valid_to;
    public $active_now;
}

function readTilgangskoder_writeToJson($file) {
    $objects = readCsvFileBatch($file);

    $tilgangskoder = array();
    foreach ($objects as $obj) {
        $tilgangskode = new Tilgangskode();

        foreach ((array)$obj as $key => $value) {
            if ($key == 'Tilgangskode' && !isset($tilgangskode->code)) {
                $tilgangskode->code = $value;
            }
            elseif ($key == 'Kode' && !isset($tilgangskode->code)) {
                $tilgangskode->code = $value;
            }
            elseif ($key == 'Beskrivelse' && !isset($tilgangskode->name_or_description)) {
                $tilgangskode->name_or_description = $value;
            }
            elseif ($key == 'Betegnelse' && !isset($tilgangskode->name_or_description)) {
                $tilgangskode->name_or_description = $value;
            }
            elseif ($key == 'Offentlig beskrivelse' && !isset($tilgangskode->note)) {
                $tilgangskode->note = $value;
            }
            elseif ($key == 'Notat' && !isset($tilgangskode->systemSortOrder)) {
                $tilgangskode->note = $value;
            }
            elseif (($key == 'Startdato' || $key == 'Fradato' || $key == 'Fra dato') && !isset($tilgangskode->valid_from)) {
                $tilgangskode->valid_from = $value;
            }
            elseif (($key == 'Sluttdato' || $key == 'Tildato' || $key == 'Til dato') && !isset($tilgangskode->valid_to)) {
                $tilgangskode->valid_to = $value;
            }
            elseif ($key == 'Aktiv' && !isset($tilgangskode->active_now)) {
                if ($value == 'Aktiv' || $value == 'x') {
                    $tilgangskode->active_now = true;
                }
                else {
                    throw new Exception('Unknown value for Aktiv: ' . $value);
                }
            }
            elseif ($key == 'Status' && !isset($tilgangskode->active_now)) {
                if ($value == 'Aktiv') {
                    $tilgangskode->active_now = true;
                }
                elseif ($value == 'Inaktiv') {
                    $tilgangskode->active_now = false;
                }
                else {
                    throw new Exception('Unknown value for Aktiv: ' . $value);
                }
            }
            elseif (($key == 'Hjemmel' || $key == 'Standard paragraf' || $key == 'Lovhjemmel' || $key == 'Paragraf') && !isset($tilgangskode->legal_basis_used_in_category)) {
                $tilgangskode->legal_basis_used_in_category = $value;
            }
            elseif (($key == 'Stikkord') && !isset($tilgangskode->legal_basis_used_in_category_hint)) {
                $tilgangskode->legal_basis_used_in_category_hint = $value;
            }
            elseif ($key == 'System' && !isset($tilgangskode->system)) {
                $tilgangskode->system = $value;
            }
            elseif (($key == 'ID' || $key == 'Id') && !isset($tilgangskode->codeSystemId)) {
                $tilgangskode->codeSystemId = $value;
            }
            elseif ($key == 'Sortering' && !isset($tilgangskode->systemSortOrder)) {
                $tilgangskode->systemSortOrder = $value;
            }
            elseif ($key == 'Sorterings ID' && !isset($tilgangskode->systemSortOrder)) {
                $tilgangskode->systemSortOrder = $value;
            }
            elseif ($key == 'Tillates ekspedert med epost' || $key == 'Sikkerhetsnivå' || $key == 'Tillates ekspedert' || $key == 'Tillatt ekspedering til SDP') {
                // Ignore
            }
            elseif ($key == '' && $value == '') {
                // Ignore
            }
            else {
                var_dump($obj);
                echo "File ..... : $file\n";
                throw new Exception('Unknown fields: ' . $key);
            }
        }

        $no_null_fields = array();
        foreach ((array)$tilgangskode as $key => $value) {
            if ($value == null) {
                continue;
            }
            $no_null_fields[$key] = $value;
        }
        $tilgangskoder[] = $no_null_fields;
    }

    if (count($tilgangskoder) > 0) {
        file_put_contents($file . '.json', json_encode($tilgangskoder, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
    }
}