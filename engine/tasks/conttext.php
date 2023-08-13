<?
function contact($key, $table)
{
    global $_LOADER;

    $SQL = "ALTER TABLE  ".$table." ADD  contact_old TEXT NOT NULL";
    @$_LOADER->ExecuteSafe($SQL);

    $SQL = "select ".$key.", contact from ".$table;
    $dump = $_LOADER->LFetchRows($SQL);

    foreach ($dump as $item) {
        $contacts = explode(";", $item[1]);
        $q = array();

        foreach ($contacts as $a)
        {
            if (preg_match("#\[(.+?)(=.+)?\](.+)#ms", $a, $matches))
            {
                $contact = new TNativeContact();

                if ($matches[1] == "mob") {
                    $contact->type = $matches[1];
                    $contact->text = str_replace("=", "", $matches[2]);

                    if (preg_match("#(.+)?\((.+)?\)(.+)?#ms", $matches[3], $data)) {
                        $contact->phone = new TNativeContactPhone();
                        $contact->phone->code = $data[1];
                        $contact->phone->ops = $data[2];
                        $contact->phone->data = $data[3];
                    }
                } else {
                    $contact->type = $matches[1];
                    $contact->text = str_replace("=", "", $matches[2]);
                    $contact->data = str_replace("=", "", $matches[3]);
                }
                array_push($q, $contact);
                unset($contact);
            }
        }
        $SQL = "update ".$table." set contact_old=contact, contact=compress('".serialize($q)."') where ".$key."=".$item[0];
        $_LOADER->Execute($SQL);
    }
}

contact("ID_ANNOUNCE", "ANNOUNCE_DATA");
contact("ID_USER", "USER_DATA");
echo "contact ok";

?>
