<?php

    require_once 'database.php';

    //Initialize every variable
    $pack = $number_participant = $mode_paiement = $phone_number = $response = "";

    if ($_POST) 
    {
        if (!empty($_POST['user_id']) && !empty($_POST['pack_id']) && !empty($_POST['number_participant']) && isset($_POST['mode_paiement']) && isset($_POST['phone_number']))
        {   
            if (isPhone($_POST['phone_number'])) 
            {
                $user_id = securisation($_POST['user_id']);
                $pack_id = securisation($_POST['pack_id']);
                $number_participant = securisation($_POST['number_participant']);
                $mode_paiement = securisation($_POST['mode_paiement']);
                $phone_number = securisation($_POST['phone_number']);
    
                SendReservation();
            } 
            else 
            {
                $response=array(
                    'status' => 0,
                    'status_message' => 'Que des chiffres et des espaces pour le Numéro de téléphone !',
                );
            }
        }
        else 
        {
            $response=array(
                'status' => 0,
                'status_message' => 'Aucun paramètre fourni. Fournissez tous les paramètres !',
            );
        }
    }
    else 
    {
        $response=array(
            'status' =>0,
            'status_message' => 'Aucun paramètre fourni !',
        );
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    


    //Send reservation data to user_pack
    function SendReservation()
    {
        global $user_id, $pack_id, $number_participant, $mode_paiement, $phone_number;
        // Se connecter à la base de données
        $bdd = Database::connect();
        
        //Check if user_id exists
        $query = $bdd->prepare("SELECT * FROM users WHERE users.id = ?");
        $query->execute(array($user_id));
        if ($query->rowCount() == 1) 
        {
            //Check if pack_id exists
            $query = $bdd->prepare("SELECT * FROM pack WHERE pack.id = ?");
            $query->execute(array($pack_id));
            if ($query->rowCount() == 1) 
            {
                $data = $query->fetch();
                //Generate reference
                $longueurkey = 11;
                $reference = "";
                for ($i=1; $i < $longueurkey; $i++)
                { 
                    $reference .= mt_rand(0,9);
                }

                //Value of reservation set null
                $is_reserved = 0;

                if ($number_participant <= $data['limit_person']) 
                {
                    //Insertion in user_pack
                    $insertion = $bdd->prepare("INSERT INTO user_pack(user_id, pack_id, number_participant, reference, is_reserved) VALUES(?,?,?,?,?)");

                    $insertion->execute(array($user_id, $pack_id, $number_participant, $reference, $is_reserved));

                    $last_id = $bdd->lastInsertId(); // Get the last inserted ID
                   
                    $insert = true;

                    if ($insert) 
                    {
                        //Incrementation of Count_reservation in pack
                        $NewValue = $data['count_reservation'] + 1;
                        $insert_count = $bdd->prepare("UPDATE pack SET count_reservation = $NewValue WHERE id = $pack_id");

                        $insert_count->execute();

                        Database::disconnect();
                        
                        //Get mode of payement
                        if ($mode_paiement == 'Mobile Money') 
                        {
                            //Get the ID reservation
                            $response=array(
                                'id_reservation' => $last_id,
                                'phone_number' => $phone_number,
                                'mode_paiement' => 'Mobile Money',
                            );
                        }
                        elseif ($mode_paiement == 'Carte Bancaire') 
                        {
                            //Get the ID reservation
                            $response=array(
                                'id_reservation' => $last_id,
                                'mode_paiement' => 'Carte Bancaire',
                            );
                        } 
                    }
                    else 
                    {
                        //Insertion into BDD no 
                        $response=array(
                            'status' =>0,
                            'status_message' => 'La réservation n\'a pas marché. Veuillez revoir vos informations fournis !',
                        );
                    }
                } 
                else 
                {
                    $response=array(
                        'status' => 0,
                        'status_message' => 'Le nombre de personne limite pour ce pack est ' .$data['limit_person']. '. Veuillez rester dans la limite !',
                    );
                }
                
                
            }
            else 
            {
                $response=array(
                    'status' =>'Not found',
                    'status_message' => 'Ce pack n\'existe pas dans notre base !',
                );
            }
        }
        else 
        {
            $response=array(
                'status' =>'Not found',
                'status_message' => 'Ce utilisateur n\'existe pas dans notre base !',
            );
        }
        header('Content-Type: application/json');
        echo json_encode($response);
    }


    //Data securing
    function securisation($donnee)
    {
        $donnee = trim($donnee);
        $donnee = stripslashes($donnee);
        $donnee = strip_tags($donnee);
        $donnee = htmlspecialchars($donnee);
        return $donnee;
    }


    //Phone validity
	function isPhone($var)
	{
		return preg_match("/^[0-9 ]*$/", $var);
	}

?>