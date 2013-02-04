<?php
/**
 * fonctionsFonctionsModel : un namespace de fonctions diverses
 *
 * @package
 * @version $id$
 * @copyright
 * @author Pierre-Alexis <pa@quai13.com>
 * @license
 */
class fonctionsFonctionsModel extends fonctionsFonctionsModel_Parent
{

    /**
     * htmlentities : wrapper pour htmlentities afin de lui faire utiliser le bon encodage par defaut (celui du site, et non ISO-8859-1)
     * 
     * @param mixed $string 
     * @param mixed $quote_style 
     * @param mixed $charset 
     * @param mixed $double_encode 
     * @access public
     * @return void
     */
    public function htmlentities ($string, $quote_style = ENT_COMPAT, $charset = null, $double_encode = true)
    {
        if ($charset === null) {
            $charset = mb_internal_encoding();
        }
        // php < 5.2.3 compatibility
        if (!$double_encode) {
            return htmlentities($string, $quote_style, $charset, $double_encode);
        }
        return htmlentities($string, $quote_style, $charset);
    }

    /**
     * html_entity_decode : wrapper pour html_entity_decode afin de lui faire utiliser le bon encodage par defaut (celui du site, et non ISO-8859-1)
     * 
     * @param mixed $string 
     * @param mixed $quote_style 
     * @param mixed $charset 
     * @access public
     * @return void
     */
    public function html_entity_decode ($string, $quote_style = ENT_COMPAT, $charset = null)
    {
        $nbsp = ' ';
        if ($charset === null) {
            $charset = mb_internal_encoding();
        } else {
            $nbsp = iconv(mb_internal_encoding(), $charset, $nbsp);
        }
        // l'entite HTML nbsp est mal decodee, et plante la fonction trim()... on contourne donc en la decodant manuellement
        return html_entity_decode(str_replace('&nbsp;', $nbsp, $string), $quote_style, $charset);
    }

    /**
     * strip_tags : wrapper pour strip_tags, qui peut preserver les tags desires tout en supprimant quand meme leurs attributs, et qui peut préserver les attributs désirés. Pratique contre les failles XSS / CSRF
     * 
     * @param mixed $string
     * @param mixed $allowtags
     * @param mixed $allowattributes
     * @access public
     * @return void
     */
    public function strip_tags ($string, $allowtags = null, $allowattributes = null)
    {
        $string = strip_tags($string, $allowtags);
        if (!is_null($allowattributes)) {
            if (!is_array($allowattributes)) {
                $allowattributes = explode(",", $allowattributes);
            }
            if (is_array($allowattributes)) {
                $allowattributes = implode(")(?<!", $allowattributes);
            }
            if (strlen($allowattributes) > 0) {
                $allowattributes = "(?<!" . $allowattributes . ")";
            }
            $string = preg_replace_callback("/<[^>]*>/i", create_function('$matches',
                                                                          'return preg_replace("/ [^ =]*' . $allowattributes . '=(\"[^\"]*\"|\'[^\']*\')/i", "", $matches[0]);'), $string);
        }
        return $string;
    }

    /**
     * substr : wrapper substr compatible utf8
     * 
     * @param mixed $string 
     * @param mixed $start 
     * @param mixed $lenght 
     * @param mixed $charset 
     * @access public
     * @return void
     */
    public function substr ($string, $start, $length = null, $charset = null)
    {
        if ($charset === null) {
            $charset = mb_internal_encoding();
        }
        // corrige le dans la gestion du parametre length (mb_substr interprette null comme 0)
        if ($length) {
            return mb_substr($string, $start, $length, $charset);
        } else {
            return mb_substr($string, $start, $this->strlen($string), $charset);
        }
    }

    /**
     * strlen : wrapper strlen compatible utf8
     * 
     * @param mixed $string 
     * @param mixed $start 
     * @param mixed $lenght 
     * @param mixed $charset 
     * @access public
     * @return void
     */
    public function strlen ($string, $charset = null)
    {
        if ($charset === null) {
            $charset = mb_internal_encoding();
        }
        return mb_strlen($string, $charset);
    }

    // =======================================
    // Fonctions de récupération de paramètres
    // =======================================

    /**
     * ifSet : wrapper de isset pour les tableau notamment get post et cookie... à utiliser à la place de $_GET, $_POST et $_COOKIE... afin d'eviter les failles basees sur l'utilisation de ces tableaux
     * 
     * @param mixed $tableau 
     * @param mixed $key 
     * @access public
     * @return void
     */
    public function ifSet ($key, $tableau)
    {
        $bool = false;
        switch ($tableau) {
            case 'get' : 
                $bool = isset($_GET[$key]);
                break;
            case 'post' : 
                $bool = isset($_POST[$key]);
                break;
            case 'cookie' : 
                $bool = isset($_COOKIE[$key]);
                break;
            default :
                if (is_array($tableau)) {
                    $bool = isset($key, $tableau);
                }
                break;
        }
        return $bool;
    }

    /**
     * ifGet : wrapper pour ifSetGet qui recupere un parametre dans $_GET
     * 
     * @param mixed $type 
     * @param mixed $key 
     * @param mixed $ifset 
     * @param mixed $ifnotset 
     * @param int $non_vide 
     * @param int $trim 
     * @param int $striptags 
     * @access public
     * @return void
     */
    public function ifGet ($type, $key, $ifset = null, $ifnotset = null, $non_vide = 0, $trim = 0, $striptags = 1)
    {
        return $this->ifSetGetGPC($type, $_GET, $key, $ifset, $ifnotset, $non_vide, $trim, $striptags);
    }

    /**
     * ifPost : wrapper pour ifSetGet qui recupere un parametre dans $_POST
     * 
     * @param mixed $type 
     * @param mixed $key 
     * @param mixed $ifset 
     * @param mixed $ifnotset 
     * @param int $non_vide 
     * @param int $trim 
     * @param int $striptags 
     * @access public
     * @return void
     */
    public function ifPost ($type, $key, $ifset = null, $ifnotset = null, $non_vide = 0, $trim = 0, $striptags = 1)
    {
        return $this->ifSetGetGPC($type, $_POST, $key, $ifset, $ifnotset, $non_vide, $trim, $striptags);
    }

    /**
     * ifCookie : wrapper pour ifSetGet qui recupere un parametre dans $_COOKIE
     * 
     * @param mixed $type 
     * @param mixed $key 
     * @param mixed $ifset 
     * @param mixed $ifnotset 
     * @param int $non_vide 
     * @param int $trim 
     * @param int $striptags 
     * @access public
     * @return void
     */
    public function ifCookie ($type, $key, $ifset = null, $ifnotset = null, $non_vide = 0, $trim = 0, $striptags = 1)
    {
        return $this->ifSetGetGPC($type, $_COOKIE, $key, $ifset, $ifnotset, $non_vide, $trim, $striptags);
    }

    /**
     * ifSetGetGPC : wrapper pour ifSetGet qui recupere un parametre dans un tableau potentiellement dangereux, tel que $_GET, $_POST...
     * 
     * @param mixed $type 
     * @param mixed $key 
     * @param mixed $ifset 
     * @param mixed $ifnotset 
     * @param int $non_vide 
     * @param int $trim 
     * @param int $striptags 
     * @access public
     * @return void
     */
    public function ifSetGetGPC ($type, $tableau, $key, $ifset = null, $ifnotset = null, $non_vide = 0, $trim = 0, $striptags = 1)
    {
        // raccourci pour recuperer plus facilement des contenus HTML
        $typehtml = 0;
        if ($type == 'html') {
            $striptags = 0;
            $type = 'string';
            $typehtml = 1;
        }
        $r = $this->ifSetGet($type, $tableau, $key, $ifset, $ifnotset, $non_vide, $trim, $striptags);
        if (!get_magic_quotes_gpc()) {
            $r = addslashes($r);
        }
        // EN AJAX, ATTENTION A L'ENCODAGE : pour s'assurer que les caractères accentués (ou le sigle euro par exemple) sont bien transmis, il FAUT encoder l'URL
        // En JAVASCRIPT on utilisera la fonction encodeURIComponent
        // En PHP on utilisera la fonction rawurlencode() pour obtenir le meme encodage (et non urlencode(), qui n'encode pas les espaces pareil)
        if ($typehtml) {
            $r = preg_replace('@<script[^>]*?>.*?</script>@si', '', $r);
        } else {
            if ($type == 'array') {
                foreach ($r as $subkey => $val) {
                    $r[$subkey] = $this->htmlentities(stripslashes($r[$subkey]), ENT_QUOTES);
                }
            } else {
                $r = $this->htmlentities(stripslashes($r), ENT_QUOTES);
            }
        }
        return $r;
    }

    /**
     * ifSetGet : fonction centrale de la recuperation de paramètres : recupere le parametre $key dans le tableau $tableau
     * 
     * @param mixed $type : force le typage
     * @param mixed $tableau : $_GET, $_POST, ou $_COOKIE... ou n'importe quel tableau
     * @param mixed $key : nom du parametre à recuperer
     * @param mixed $ifset : valeur a récupérer a la place du paramètre si celui si existe bien dans $tableau
     * @param mixed $ifnotset : valeur a récupérer a la place du paramètre si celui si n'existe pas dans $tableau
     * @param int $non_vide : si $tableau[$key] == '' et que ce parametre est positionne, on considere que !isset($tableau[$key]). On renvoie donc $ifnotset le cas echeant.
     * @param int $trim : 0 => pas de trim, 1 => trim normal, 2 => trim violent (vire aussi tous les retours a la ligne).
     * @param int $striptags : 0 => pas de strip_tags, 1 => strip_tags, '<p><a>' => liste des tags autorises, strip tous les autres
     * @access public
     * @return void
     */
    public function ifSetGet ($type, $tableau, $key, $ifset = null, $ifnotset = null, $non_vide = 0, $trim = 0, $striptags = 1)
    {
        if (!isset($tableau[$key])) {
            // securite !
            @settype($ifnotset, $type);
            return $ifnotset;
        } else {
            if ($striptags) {
                $striptags_tags = ((strlen($striptags) && $striptags != 1) ? $striptags : ''); // tags a preserver
                if ($type == 'array') {
                    foreach ($tableau[$key] as $subkey => $val) {
                        $tableau[$key][$subkey]  = $this->strip_tags($tableau[$key][$subkey], $striptags_tags);
                    }
                } else {
                    $tableau[$key]  = $this->strip_tags($tableau[$key], $striptags_tags);
                }
            }
            if ($trim) {
                $tableau[$key] = trim($tableau[$key]);
            }
            if ($trim == 2) {
                $tableau[$key] = trim(preg_replace("/((\r)*\n*)*/", "", $tableau[$key]));
            }
            if (!(!$non_vide || ($non_vide && strlen($tableau[$key])))) {
                return $ifnotset;
            }
            if (isset($ifset) && !is_array($ifset)) {
                return $ifset;
            } else {
                $ret = $tableau[$key];
                // securite !
                @settype($ret, $type);
                // si ifset est un tableau, on renvoie la concatenation de ses elements, en concatenant $var a chacun
                if (is_array($ifset)) {
                    $retour = '';
                    $ifset_sz = count($ifset) - 1;
                    if ($ifset_sz < 1) {
                        $ifset_sz = count($ifset);
                    }
                    for ($i = 0; $i < $ifset_sz; ++$i) {
                        $retour .= $ifset[$i] . $ret;
                    }
                    if ($ifset_sz == count($ifset) - 1) {
                        $retour .= $ifset[$ifset_sz];
                    }
                    return $retour;
                } else {
                    return $ret;
                }
            }
        }
    }

    // ==================================================
    // Fonctions de manipulation de chaines de caractères
    // ==================================================

    /**
     * remove_accents : supprime les accents de la chaine $str (par transliteration en ASCII)
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function remove_accents ($str)
    {
        if (!extension_loaded('iconv')) {
            trigger_error('Extension PHP ICONV manquante', E_USER_WARNING);
        }
        return iconv(__PHP_ENCODING__, 'US-ASCII//TRANSLIT', $str);
    }

    // ===============================
    // Fonctions de manipulation d'URL
    // ===============================

    /**
     * redirect : effectue une redirection HTTP par un header(), avec fallback Javascript et au pire, écrit le lien si le UserAgent ne suit aucune des redirections précédentes.
     * 
     * @param mixed $url 
     * @param int $code_http 
     * @access public
     * @return void
     */
    public function redirect ($url, $code_http = 302) 
    {
        header('Location: ' . $url, true, $code_http);
        // echo('<script type="text/Javascript">setTimeout("document.location = \'' . $url . '\'", 3000);</script><noscript>Redirected to <a href="' . $url . '">' . $url . '</a></noscript>');
        die();
    }

    /**
     * del_param : supprime le parametre $param de la chaine $url
     * 
     * @param mixed $url 
     * @param mixed $param 
     * @param mixed $valeur : ne supprime le parametre que s'il a la valeur $valeur
     * @access public
     * @return void
     */
    public function del_param ($url, $param, $valeur = null)
    {
        if ($valeur == null) {
            $valeur = '[^&]*';
        }
        $recherche = array('/(.*?)' . $param . '=' . $valeur . '(.*)/', '/&&/', '/&$/', '/\?&/');
        $remplace  = array('$1$2', '&', '', '?');
        return preg_replace($recherche, $remplace, $url);
    }

    /**
     * add_param : ajoute le parametre $param avec la valeur $valeur dans la chaine $url
     * 
     * @param mixed $url 
     * @param mixed $param 
     * @param mixed $valeur 
     * @access public
     * @return void
     */
    public function add_param ($url, $param, $valeur)
    {
        if ((strpos($url, '&')) !== false || (strpos($url, '?')) !== false) {
            $url .= '&' . $param . '=' . $valeur;
        } else {
            $url .= '?' . $param . '=' . $valeur;
        }
        $recherche = array('/&&/', '/&$/', '/\?&/');
        $remplace  = array('&', '', '?');
        return preg_replace($recherche, $remplace, $url);
    }

    /**
     * mod_param : ajoute ou modifie la valeur du parametre $param dans la chaine $url
     * 
     * @param mixed $url 
     * @param mixed $param 
     * @param mixed $valeur 
     * @access public
     * @return void
     */
    public function mod_param ($url, $param, $valeur)
    {
        $url = $this->del_param($url, $param);
        $url = $this->add_param($url, $param, $valeur);
        //$url = preg_replace ('/(.*)([?&]*)'.$param.'=([^&]*)(.*)/', '/$1$2'.$param.'='.$valeur.'$4/', $url);
        return $url;
    }

    /**
     * urlize : remplace les caracteres qui ne doivent pas etre presents pour faire des mots cles dans une URL par exemple
     * 
     * @param mixed $element 
     * @access public
     * @return void
     */
    public function urlize ($element) 
    {
        $element_clean = $this->html_entity_decode(stripslashes($element), ENT_QUOTES);
        $element_clean = $this->strip_tags($element_clean);
        $element_clean = $this->remove_accents($element_clean);
        $element_clean = strtolower($element_clean);
        $element_clean = trim(preg_replace('/[^a-zA-Z0-9@+\. ]/', ' ', $element_clean));
        $element_clean = preg_replace('/ /',  '-', $element_clean);
        $element_clean = preg_replace('/--*/', '-', $element_clean);
        return $element_clean;
    }

    /**
     * isajax : renvoie vrai si la page a ete appellee en AJAX, faux sinon 
     * 
     * @access public
     * @return void
     */
    public function isajax() 
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    // ==============================
    // Fonctions pour les formulaires
    // ==============================
    /**
     * get_max_filesize : retourne la taille maximale (en octets) de fichier qu'on peut uploader sur ce serveur ! 
     * 
     * @access public
     * @return void
     */
    public function get_max_filesize ()
    {
        $tab = array();
        $tab[] = strtoupper(ini_get('upload_max_filesize'));
        $tab[] = strtoupper(ini_get('post_max_size'));
        // TODO : max_input_time ? comment le gérer ?
        // TODO : memory_limit ? comment le gérer ?
        // on convertit tout en octets !
        $cnt_tab = count($tab);
        for ($i = 0; $i < $cnt_tab; ++$i) {
            $unite = preg_replace('/[0-9]*/', '', $tab[$i]);
            switch ($unite) {
                case '' :
                    break;
                case 'K' :
                    $tab[$i] = $tab[$i] * 1024;
                    break;
                case 'M' :
                    $tab[$i] = $tab[$i] * 1024 * 1024;
                    break;
                case 'G' :
                    $tab[$i] = $tab[$i] * 1024 * 1024 * 1024;
                    break;
                default :
                    trigger_error('Unite inconnue dans la fonction utilisateur get_max_filesize()', E_USER_WARNING);
                    break;
            }
        }
        $min = min($tab);
        return $min;
    }

    /**
     * est_email : renvoie true si $str ressemble bien à une adresse email (x@y.z)
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function est_email ($str)
    {
        return preg_match('/^[a-z0-9\._+-]+@([a-z0-9-]+\.)+[[:alpha:]]+$/i', $str);
    }

    /**
     * est_email_jetable : renvoie true si $email fait est une adresse email jetable facilement identifiable
     * 
     * @param mixed $email 
     * @access public
     * @return void
     */
    public function est_email_jetable ($email)
    {
        $tab_blacklist = array("brefmail.com" => 0,
                               "destroy-spam.com" => 0,
                               "email-jetable.eu" => 0,
                               "filzmail.com" => 0,
                               "guerrillamailblock.com" => 0,
                               "haltospam.com" => 0,
                               "jetable.org" => 0,
                               "junk.yourdomain.com" => 0,
                               "link2mail.net" => 0,
                               "maileater.com" => 0,
                               "mailinator.com" => 0,
                               "mytempemail.com" => 0,
                               "poep.joliekemulder.nl" => 0,
                               "spamfree.eu" => 0,
                               "spamfree24.com" => 0,
                               "spamfree24.de" => 0,
                               "spamfree24.info" => 0,
                               "spamfree24.org" => 0,
                               "tempemail.co.za" => 0,
                               "tempomail.fr" => 0,
                               "terafilehosting.net" => 0,
                               "trash2009.com" => 0,
                               "uggsrock.com" => 0,
                               "yopmail.com" => 0);
        $ndd = explode('@', $email);
        return (isset($ndd[1]) && (isset($tab_blacklist[$ndd[1]])));
    }

    /**
     * get_real_ip : renvoie l'adresse ip (valeur sécurisée) de l'utilisateur meme s'il passe par un proxy
     * 
     * @param mixed $email 
     * @access public
     * @return void
     */
    public function get_real_ip ()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            //check for ip from share internet
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            // Check for the Proxy User
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        // securise les valeurs
        $ip_array = explode('.', $ip);
        $cnt = count($ip_array);
        for ($i = 0; $i < $cnt; ++$i) {
            if ((string) $ip_array[$i] === (string) ((int) $ip_array[$i])) {
                $ip_array[$i] = (string) ((int) $ip_array[$i]);
            } else {
                return false;
            }
        }
        return implode('.', $ip_array);
    }

    /**
     * envoie_mail : fonction securisee pour envoyer un mail
     * 
     * @param mixed $dest : adresse email destinataire
     * @param mixed $exp : adresse email expediteur
     * @param mixed $societe : nom expediteur
     * @param mixed $titre : titre du mail
     * @param mixed $message_text : contenu texte du message
     * @param mixed $message_html : contenu html du message
     * @param mixed $style : style du mail HTML
     * @param mixed $demande_ar : demander un accuse de reception
     * @access public
     * @return void
     */
    public function envoie_mail ($dest, $exp, $societe, $titre, $message_text, $message_html = null, $style = null, $demande_ar = null)
    {
        // Securite antispam : les emails ne doivent contenir que des caracteres mail et il ne doit pas y avoir de retours a la ligne avant le titre
        $dest       = preg_replace("/[^a-zA-Z0-9@\._+-]/", "", $dest);
        $exp        = preg_replace("/[^a-zA-Z0-9@\._+-]/", "", $exp);
        $societe    = preg_replace("/;*/",  "", $societe);
        $societe    = preg_replace("/\r*/", "", $societe);
        $societe    = preg_replace("/\n*/", "", $societe);
        $titre      = preg_replace("/\r*/", "", $titre);
        $titre      = preg_replace("/\n*/", "", $titre);
        // MIME BOUNDARY
        $mime_boundary = "---- " . $societe . " ----" . md5(time());
        // MAIL HEADERS
        $headers  = "From: " . $societe . " <" . $exp . ">\n";
        $headers .= "Reply-To: " . $societe . " <" . $exp . ">\n";
        $headers .= "Return-Path: " . $societe . " <" . $exp . ">\n";
        if ($demande_ar) {
            $headers .= "Disposition-Notification-To: " . $societe . " <" . $exp . ">\n";
            $headers .= "Return-Receipt-To: " . $societe . " <" . $exp . ">\n";
        }
        $headers .= "MIME-Version: 1.0\n";
        if (strlen($message_html)) {
            $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
        }
        // TEXT EMAIL PART
        $message = "";
        if (strlen($message_html)) {
            $message .= "\n--$mime_boundary\n";
            $message .= "Content-Type: text/plain; charset=" . __PHP_ENCODING__ . "\n";
            $message .= "Content-Transfer-Encoding: 8bit\n\n";
        }
        $message .= $this->html_entity_decode(stripslashes($message_text));
        // HTML EMAIL PART
        if (strlen($message_html)) {
            $message .= "\n--$mime_boundary\n";
            $message .= "Content-Type: text/html; charset=" . __PHP_ENCODING__ . "\n";
            $message .= "Content-Transfer-Encoding: 8bit\n\n";
            $message .= "<html>\n";
            $message .= "<body>\n";
            $message .= stripslashes($message_html);
            $message .= "</body>\n";
            $message .= "</html>\n";
            // FINAL BOUNDARY
            $message .= "\n--$mime_boundary--\n\n";
        }
        $resultat = mail($dest, $titre, $message, $headers);
        return $resultat;
    }

    // =======================
    // Fonctions pour le debug
    // =======================
    /**
     * print_pre : print_r entre balises <pre /> pour une meilleure lisibilite
     * 
     * @param mixed $mixed 
     * @param string $color 
     * @param mixed $nom 
     * @access public
     * @return void
     */
    public function print_pre ($mixed = null, $color = 'black', $nom = null)
    {
        echo '<pre style="text-align: left; font-size:11px; color:' . $color . '">';
        if (strlen($nom)) {
            echo $nom . " : ";
        }
        print_r($mixed);
        echo '</pre>';
        return null;
    }

    /**
     * echo_net : echo() plus visible
     * 
     * @param mixed $string 
     * @access public
     * @return void
     */
    public function echo_net($string)
    {
        $this->_xDumpVar($string);
    }

    /**
     * print_r_net : print_r() plus visible
     * 
     * @param array $array 
     * @access public
     * @return void
     */
    public function print_r_net($array)
    {
        $this->_xDumpVar($array);
    }

    /**
     * Dump a var
     *
     * @access private
     * @param mixed $data
     * @return string
     */
    private function _xDumpVar($data)
    {
        $B_echo = true;
        ob_start();
        var_dump($data);
        $c = ob_get_contents();
        ob_end_clean();
        $c = preg_replace("/\r\n|\r/", "\n", $c);
        $c = str_replace("]=>\n", '] = ', $c);
        $c = preg_replace('/= {2,}/', '= ', $c);
        $c = preg_replace("/\[\"(.*?)\"\] = /i", "[$1] = ", $c);
        $c = preg_replace('/    /', "        ", $c);
        $c = preg_replace("/\"\"(.*?)\"/i", "\"$1\"", $c);
        $c = htmlspecialchars($c, ENT_NOQUOTES);
        // Expand numbers (ie. int(2) 10 => int(1) 2 10, float(6) 128.64 => float(1) 6 128.64 etc.)
        $c = preg_replace("/(int|float)\(([0-9\.]+)\)/ie", "'$1('." . $this->strlen('$2') . ".') <span class=\"number\">$2</span>'", $c);
        // Syntax Highlighting of Strings. This seems cryptic, but it will also allow non-terminated strings to get parsed.
        $c = preg_replace("/(\[[\w ]+\] = string\([0-9]+\) )\"(.*?)/sim", "$1<span class=\"string\">\"", $c);
        $c = preg_replace("/(\"\n{1,})( {0,}\})/sim", "$1</span>$2", $c);
        $c = preg_replace("/(\"\n{1,})( {0,}\[)/sim", "$1</span>$2", $c);
        $c = preg_replace("/(string\([0-9]+\) )\"(.*?)\"\n/sim", "$1<span class=\"string\">\"$2\"</span>\n", $c);
        $regex = array(// Numberrs
                       'numbers' => array('/(^|] = )(array|float|int|string|resource|object\(.*\)|\&amp;object\(.*\))\(([0-9\.]+)\)/i', '$1$2(<span class="number">$3</span>)'),
                       // Keywords
                       'null' => array('/(^|] = )(null)/i', '$1<span class="keyword">$2</span>'),
                       'bool' => array('/(bool)\((true|false)\)/i', '$1(<span class="keyword">$2</span>)'),
                       // Types
                       'types' => array('/(of type )\((.*)\)/i', '$1(<span class="type">$2</span>)'),
                       // Objects
                       'object' => array('/(object|\&amp;object)\(([\w]+)\)/i', '$1(<span class="object">$2</span>)'),
                       // Function
                       'function' => array('/(^|] = )(array|string|int|float|bool|resource|object|\&amp;object)\(/i', '$1<span class="function">$2</span>('));
        foreach ($regex as $x) {
                $c = preg_replace($x[0], $x[1], $c);
        }
        $style = '
        /* outside div - it will float and match the screen */
        .dumpr {
                margin: 2px;
                padding: 2px;
                background-color: #fbfbfb;
                float: left;
                clear: both;
        }
        /* font size and family */
        .dumpr pre {
                color: #000000;
                text-align:left;
                font-size: 9pt;
                font-family: "Courier New",Courier,Monaco,monospace;
                margin: 0px;
                padding-top: 5px;
                padding-bottom: 7px;
                padding-left: 9px;
                padding-right: 9px;
        }
        /* inside div */
        .dumpr div {
                background-color: #fcfcfc;
                border: 1px solid #d9d9d9;
                float: left;
                clear: both;
        }
        /* syntax highlighting */
        .dumpr span.string {color: #c40000;}
        .dumpr span.number {color: #ff0000;}
        .dumpr span.keyword {color: #007200;}
        .dumpr span.function {color: #0000c4;}
        .dumpr span.object {color: #ac00ac;}
        .dumpr span.type {color: #0072c4;}
        .legenddumpr {
            background-color: #fcfcfc;
            border: 1px solid #d9d9d9;
            padding: 2px;
        }
        ';
        $style = preg_replace("/ {2,}/", "", $style);
        $style = preg_replace("/\t|\r\n|\r|\n/", "", $style);
        $style = preg_replace("/\/\*.*?\*\//i", '', $style);
        $style = str_replace('}', '} ', $style);
        $style = str_replace(' {', '{', $style);
        $style = trim($style);
        $c = trim($c);
        $c = preg_replace("/\n<\/span>/", "</span>\n", $c);
        $S_from = '';
        // Nom du fichier appelant la fonction
        $A_backTrace = debug_backtrace();
        if (is_array($A_backTrace) && array_key_exists(0, $A_backTrace)) {
            $S_from = <<< BACKTRACE
                {$A_backTrace[1]{'file'}}, ligne {$A_backTrace[1]{'line'}}
BACKTRACE;
        } else {
            $S_from = basename($_SERVER['SCRIPT_FILENAME']);
        }
        $S_out  = '';
        $S_out .= "<style type=\"text/css\">" . $style . "</style>\n";
        $S_out .= '<fieldset class="dumpr">';
        $S_out .= '<legend class="legenddumpr">' . $S_from . '</legend>';
        $S_out .= '<pre>' . $c . '</pre>';
        $S_out .= '</fieldset>';
        $S_out .= "<div style=\"clear:both;\">&nbsp;</div>";
        if ($B_echo) {
            echo $S_out;
        } else {
            return $S_out;
        }
    }

    // ==================================
    // Fonctions de manipulation d'images
    // ==================================

    /**
     * imagecreatefrom_x : ouvre l'image quel que soit son format : jpg, gif ou png 
     * 
     * @param mixed $filename 
     * @access public
     * @return void
     */
    public function imagecreatefrom_x ($filename)
    {
        $path_parts = pathinfo($filename);
        $ext = (strtolower($path_parts['extension']));
        switch ($ext) {
        case 'jpg' :
            return imagecreatefromjpeg($filename);
            break;
        case 'jpeg' :
            return imagecreatefromjpeg($filename);
            break;
        case 'gif' :
            return imagecreatefromgif($filename);
            break;
        case 'png' :
            return imagecreatefrompng($filename);
            break;
        default :
            return false;
            break;
        }
    }

     /**
      * img_resize : redimensionne (canevas) puis decoupe (crop) l'image, et la sauvegarde ou l'affiche (utilise la librairie GD)
      * 
      * @param mixed $args['filename']      : fichier a ouvrir
      * @param int $args['canevaswidth']    : largeur du canevas (utilise pour le redimensionnement)
      * @param int $args['canevasheight']   : hauteur du canevas (utilise pour le redimensionnement)
      * @param string $args['color']        : couleur de fond du canevas
      * @param string $args['alpha']        : 0 => pas de transparence, 1 => conserver la transparence (pour les PNG transparents par exemple)
      * @param string $args['interieur']    : 0 => l'image est redimensionnée pour etre contenue dans le canevas, 1 => l'image est redimensionnée pour contenir le canevas (utile pour le crop)
      * @param int $args['cropwidth']       : largeur de l'image finale, avec crop si necessaire
      * @param int $args['cropheight']      : hauteur de l'image finale, avec crop si necessaire
      * @param mixed $args['save_filename'] : chemin/vers/le/fichier a sauvegarder
      * @access public
      * @return void
      */
    public function img_resize ($args)
    {
        if (!extension_loaded('gd')) {
            trigger_error('Extension PHP GD manquante', E_USER_ERROR);
        }
        // valeurs obligatoires et valeurs par defaut
        if (!isset($args['filename'])) {
            trigger_error('Paramètre obligatoire "filename" manquant', E_USER_ERROR);
        }
        if (!isset($args['canevaswidth'])) {
            $args['canevaswidth'] = 0;
        }
        if (!isset($args['canevasheight'])) {
            $args['canevasheight'] = 0;
        }
        if (!isset($args['cropwidth'])) {
            $args['cropwidth'] = 0;
        }
        if (!isset($args['cropheight'])) {
            $args['cropheight'] = 0;
        }
        if (!isset($args['save_filename'])) {
            $args['save_filename'] = null;
        }
        if (!isset($args['color'])) {
            $args['color'] = '255,255,255';
        }
        if (!isset($args['alpha'])) {
            $args['alpha'] = 0;
        }
        if (!isset($args['interieur'])) {
            $args['interieur'] = 0;
        }
        $filename       = $args['filename'];
        $canevaswidth   = $args['canevaswidth'];
        $canevasheight  = $args['canevasheight'];
        $cropwidth      = $args['cropwidth'];
        $cropheight     = $args['cropheight'];
        $save_filename  = $args['save_filename'];
        $color          = $args['color'];
        $alpha          = $args['alpha'];
        $interieur      = $args['interieur'];
        // redimensionnement ssi necessaire
        if ($canevaswidth || $canevasheight) {
            // recupere les dimensions de l'image
            list($width_orig, $height_orig) = getimagesize($filename);
            $ratio_orig = $width_orig / $height_orig;
            if ($interieur == 1) {
                if ($canevasheight && (($canevaswidth / $canevasheight > $ratio_orig) || !$canevaswidth)) {
                    $canevasheight = round($canevaswidth / $ratio_orig);
                } else {
                    $canevaswidth = round($canevasheight * $ratio_orig);
                }
            } else {
                // recalcule canevaswidth en fonction de canevasheight si celui ci est specifie
                if ($canevasheight && (($canevaswidth / $canevasheight > $ratio_orig) || !$canevaswidth)) {
                    $canevaswidth = round($canevasheight * $ratio_orig);
                } else {
                    // sinon recalcule canevasheight en fonction de canevaswidth
                    $canevasheight = round($canevaswidth / $ratio_orig);
                }
            }
            // valeurs par defaut : l'image est mise a la taille de la miniature
            if (!($cropwidth && $cropheight)) {
                $cropwidth = $canevaswidth;
                $cropheight = $canevasheight;
            }
            // Resample
            $image_p = imagecreatetruecolor($cropwidth, $cropheight);
            if ($alpha != 0) {
                imagealphablending($image_p, true);
                imagesavealpha($image_p, true);
            }
            $color = explode(',', $color, 3);
            $color_red   = $color[0];
            $color_green = $color[1];
            $color_blue  = $color[2];
            if ($alpha == 0) {
                $color   = imagecolorallocate($image_p, $color_red, $color_green, $color_blue);
            } else {
                $color   = imagecolorallocatealpha($image_p, $color_red, $color_green, $color_blue, $alpha);
            }
            imagefill($image_p, 0, 0, $color);
            $image = $this->imagecreatefrom_x($filename);
            imagecopyresampled($image_p, $image, 0 - ($canevaswidth / 2) + ($cropwidth / 2), 0 - ($canevasheight / 2) + ($cropheight / 2), 0, 0, $canevaswidth, $canevasheight, $width_orig, $height_orig);
        } else {
            $image_p = $this->imagecreatefrom_x($filename);
        }
        // output : si save_filename est null on affiche directement le resultat
        if (null === $save_filename) {
            header('Content-type: image/jpeg');
        }
        if ($alpha == 0) {
            imagejpeg($image_p, $save_filename, 100);
        } else {
            imagepng($image_p, $save_filename, 9);
        }
    }

    // ==================================
    // Fonctions d'export au format Excel
    // ==================================

    /**
     * xlsBOF : ouverture du fichier Excel
     * 
     * @access public
     * @return void
     */
    public function xlsBOF ()
    {
        echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);  
    }

    /**
     * xlsEOF : fermeture du fichier Excel
     * 
     * @access public
     * @return void
     */
    public function xlsEOF ()
    {
        echo pack("ss", 0x0A, 0x00);
    }

    /**
     * xlsWriteNumber : affiche un champ de type "number"
     * 
     * @param mixed $row 
     * @param mixed $col 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function xlsWriteNumber ($row, $col, $value)
    {
        echo pack("sssss", 0x203, 14, $row, $col, 0x0);
        echo pack("d", $value);
    }

    /**
     * xlsWriteLabel : affiche un champ de type "texte" (préserve les nombres des conversions automatiques)
     * 
     * @param mixed $row 
     * @param mixed $col 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function xlsWriteLabel ($row, $col, $value)
    {
        $L = strlen($value);
        echo pack("ssssss", 0x204, 8 + $L, $row, $col, 0x0, $L);
        echo $value;
    } 


    /**
     * matrix2xls : exporte un tableau 2D (matrice) en fichier Excel (champs au format texte), en appliquant html_entity_decode
     * 
     * @param mixed $s_filename : nom fu fichier à télécharger
     * @param mixed $m_matrix : tableau 2D a exporter
     * @param mixed $a_titles : tableau des titres des colonnes
     * @param mixed $b_output_buffering : utilise par defaut la bufferisation de sortie pour mieux gérer la consommation mémoire du script
     * @access public
     * @return void
     */
    public function matrix2xls ($s_filename, $m_matrix, $a_titles = null, $b_output_buffering = true)
    {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=$s_filename;");
        header("Content-Transfer-Encoding: binary ");
        if ($b_output_buffering) {
            ob_start();
        }
        // demarre la feuille
        $this->xlsBOF();
        // ligne de titres
        $i = 0;
        $j = 0;
        if ($a_titles) {
            foreach ($a_titles as $s_title) {
                $s_title = utf8_decode($this->html_entity_decode($s_title));
                $this->xlsWriteLabel(0, $i, $s_title);
                ++$i;
            }
            $j = 1;
        }
        // affichage ligne par ligne
        foreach ($m_matrix as $a_line) {
            @set_time_limit(0); // pour que le script ne soit pas interrompu
            $k = 0;
            foreach ($a_line as $s_key => $s_elt) {
                $s_elt = utf8_decode($this->html_entity_decode($s_elt));
                $this->xlsWriteLabel($j, $k, $s_elt);
                ++$k;
            }
            // flush toutes les 1000 lignes
            if ($b_output_buffering && ($j%1000 == 0)) {
                ob_flush();
                flush();
            }
            ++$j;
        }
        // termine la feuille
        $this->xlsEOF();
        if ($b_output_buffering) {
            ob_flush();
            flush();
            ob_end_flush();
        }
        // le script doit se terminer a la fin de l'export
        die();
    }

}
?>
