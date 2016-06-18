<?php
/**
 * cron_eedomus_to_ovhdbaasts.php
 *
 * Copyright 2016 GILLARDEAU Thibaut (aka Thibautg16)
 *
 * Authors :
 *  - Gillardeau Thibaut (aka Thibautg16)
 *
 * Licensed under the Apache License, Version 2.0 (the "License"). 
 * You may not use this file except in compliance with the License. 
 * A copy of the License is located at :
 * 
 * http://www.apache.org/licenses/LICENSE-2.0.txt 
 * 
 * or in the "license" file accompanying this file. This file is distributed 
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either 
 * express or implied. See the License for the specific language governing 
 * permissions and limitations under the License. 
 */ 
 
// Api Eedomus
include('ApiEedomus.php');

// Configuration
include('configuration.php');

// Connection au serveur MYSQL
$dns = 'mysql:host='.$host.';dbname='.$nomBDD;
$connection = new PDO( $dns, $utilisateurBDD, $mdpBDD, array (PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

// Initialisation des valeurs pour la récupération de l'historique
$end_date = new DateTime();
$end_date->add(new DateInterval('PT1H'));
$end_date = $end_date->format('Y-m-d H:i:s');
$end_date_url = urlencode($end_date);
echo 'End Date : '.$end_date." \n";

// Creation de l'objet
$ApiEedomus = new ApiEedomus($api_user, $api_secret);
$periph = $ApiEedomus->getPeripheriqueListe();

// Pour chaque périphérique, on récupére l'historique des valeurs
foreach($periph->body as $p){
        // Recuperation des informations nécessaires pour la suite
        $periph_id = $p->periph_id;
        $value_type = utf8_decode($p->value_type);
        $periph_name = $p->name;
        $i=0;

        // On regarde si le périphérique est déjà dans la base, sinon on l'ajoute
        $requete = $connection->prepare('SELECT id, last_update FROM eedomus_periph WHERE periph_id = :periph_id');
        $requete->execute(array('periph_id'  => $periph_id));
        $donnees = $requete->fetch();

        if($donnees === FALSE){
                // On défini arbitrairement la date de la derniére valeur enregistrée pour ce périphérique
                $lastUpdate = '2000-01-01 00:00:00';
                
                $insert = $connection->prepare('INSERT INTO eedomus_periph VALUES(NULL, :periph_id, :parent_periph_id, :name, :value_type, :value_unite, :room_id, :room_name, :usage_id, :usage_name, :creation_date, :last_update)');
                $insert->execute(array(
                                            'periph_id'        => $periph_id,
                                            'parent_periph_id' => $p->parent_periph_id,
                                            'name'             => $p->name,
                                            'value_type'       => $p->value_type,
                                            'value_unite'      => NULL,
                                            'room_id'          => $p->room_id,
                                            'room_name'        => $p->room_name,
                                            'usage_id'         => $p->usage_id,
                                            'usage_name'       => $p->usage_name,
                                            'creation_date'    => $p->creation_date,
                                            'last_update'      => $lastUpdate,
                                        ));                
										
                // On récupére l'id de ce nouveau phériphérique
                $periph = $connection->lastinsertid();
        }
        else{
                // On récupére l'id de ce phériphérique
                $periph = $donnees['id'];
                // On récupére la date de la derniére valeur enregistrée pour ce périphérique +1 seconde
                // sinon on récupére la précédente valeur
                $lastUpdate = new DateTime($donnees['last_update']);
                $lastUpdate->add(new DateInterval('PT1S'));
                $lastUpdate = $lastUpdate->format('Y-m-d H:i:s');
        }

        $periph_name = str_replace(' ', '_', ucwords(strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($periph_name)))));
        $show_all=0;
        
        $historique = $ApiEedomus->getHistoriquePeripherique($periph_id, urlencode($lastUpdate), $end_date_url, $show_all);    

        if(!empty($historique->body->history)){
                // On inverse le tableau pour avoir les valeurs des plus anciennes aux plus récentes
                $reversed = array_reverse($historique->body->history);

                // Pour chaque valeur, on l'ajoute dans la base de données
                foreach($reversed as $v){                        
                        /********* IOT OVH *********/
                        $param = array(
                                     array(
                                        "metric" => $periph_name,
                                        "value"  => floatval($v[0]),
                                        "timestamp" => strtotime($v[1]),
                                        "tags" => array("piece" => $p->room_name)
                                     ));

                        $post = json_encode($param);

                        $process = curl_init('opentsdb-gra1.tsaas.ovh.com');
                        curl_setopt($process, CURLOPT_URL, 'https://opentsdb-gra1.tsaas.ovh.com/api/put');
                        curl_setopt($process, CURLOPT_HEADER, 1);
                        curl_setopt($process, CURLOPT_USERPWD, $idOVHTS.':'.$tokenOVHTS);
                        curl_setopt($process, CURLOPT_POST, 1);
                        curl_setopt($process, CURLOPT_POSTFIELDS, $post);
                        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
                        $return = curl_exec($process);
                        curl_close($process);
                        /********* FIN IOT OVH *********/                                       
                }
                //On update le periph avec la date/heure de la derniére maj
                $update = $connection->prepare('UPDATE eedomus_periph SET last_update = :lastUpdate WHERE periph_id = :periph_id');
                $success_up = $update->execute(array('lastUpdate' => $v[1], 'periph_id' => $periph_id));
        }
        // Petite pausse avant de passer au périphérique suivant
        sleep(3);
}
?>