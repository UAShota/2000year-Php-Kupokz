<?
    function process($_LOADER, $field, $table)
    {
        $SQL = "select ".$field.", uncompress(contact) aa, phone_code from ".$table." ad, REF_CITY rc where CONTACT<>'' and ad.ID_CITY=rc.ID_CITY";
        $dump = $_LOADER->RunNoAssoc($SQL);

        foreach ($dump as $item) {
            $contact = $item[1];
            $contact = str_replace("[ho3]8", "[ho3]", $contact);
            $contact = str_replace("8(", "", $contact);
            $contact = str_replace("+7(", "", $contact);
            $contact = str_replace("+7", "", $contact);
            $contact = str_replace("7(", "", $contact);
            $contact = str_replace(")", "", $contact);
            $contact = str_replace("(", "", $contact);

            $contacts = explode(';', $contact);
            for ($i = 0; $i < count($contacts); $i++) {
                if (
                    (strpos($contacts[$i], "ho3") !== false)
                    //|| (strpos($contacts[$i], "hom") !== false)
                    || (strpos($contacts[$i], "mob") !== false)
                ) {
                    $contacts[$i] = str_replace("-", "", $contacts[$i]);
                    $contacts[$i] = str_replace(" ", "", $contacts[$i]);
                    $contacts[$i] = str_replace("-", "", $contacts[$i]);

                    if (preg_match("#(\[mob.+?\])(\d{3})(\d{7})#Ui", $contacts[$i], $matches))
                    {
                        $contacts[$i] = $matches[1]."+7(".$matches[2].")".$matches[3];
                    }

                    /*if (preg_match("#(\[hom\])(\d+)#ms", $contacts[$i], $matches))
                    {
                        $contacts[$i] = $matches[1]."+7(".$item[2].")".$matches[2];
                    }

                    if (preg_match("#(\[ho3\])(\d{3})(\d{7})#Ui", $contacts[$i], $matches))
                    {
                        $contacts[$i] = $matches[1]."+7(".$matches[2].")".$matches[3];
                    }

                    if (preg_match("#(\[ho3\])(\d{7})#Ui", $contacts[$i], $matches))
                    {
                        $contacts[$i] = $matches[1]."+7(".$item[2].")".$matches[2];
                    }

                    if (preg_match("#(\[ho3\])(\d{6})#Ui", $contacts[$i], $matches))
                    {
                        $contacts[$i] = $matches[1]."+7(".$item[2].")".$matches[2];
                    }

                    $contacts[$i] = str_replace("ho3", "mob", $contacts[$i]);*/
                }
            }
            $contact = implode(";", $contacts).";";
            $contact = str_replace(";;", ";", $contact);
            if ($contact == ";") $contact = "";

            $SQL = "update ".$table." set contact=compress('".$contact."') where ".$field."=".$item[0];
            $_LOADER->Execute($SQL);
            echo $contact."<br>";
        }
    }

    //process($_LOADER, "id_announce", "ANNOUNCE_DATA");
    //process($_LOADER, "id_user", "USER_DATA");
    process($_LOADER, "id_company", "COMPANY_DATA");
?>