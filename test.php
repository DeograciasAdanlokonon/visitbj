<?php

    require 'vendor\autoload.php';

    require_once('fedapay-php/init.php');

    try 
    {
      //STEP 1
      /* Remplacez VOTRE_CLE_API par votre véritable clé API */
      \FedaPay\FedaPay::setApiKey("sk_sandbox_MKQovp-HGppu1785kugMgRg1");

      /* Précisez si vous souhaitez exécuter votre requête en mode test ou live */
      \FedaPay\FedaPay::setEnvironment('sandbox'); //ou setEnvironment('live');

      /* Créer la transaction */
      $transaction = \FedaPay\Transaction::create(array(
        "description" => "Transaction for john.doe@example.com",
        "amount" => 100000,
        "currency" => ["iso" => "XOF"],
        "callback_url" => "https://maplateforme.com/callback",
        "customer" => [
            "firstname" => "John",
            "lastname" => "Doe",
            "email" => "john.doe@example.com",
            "phone_number" => [
                "number" => "+22997808080",
                "country" => "bj"
            ]
        ]
      ));

      //STEP 2
      $token = $transaction->generateToken();
      return header('Location: ' . $token->url);

      //STEP 3
      //Get ID of the transaction
      $id =  intval($_POST["id"]);

      $transaction = \FedaPay\Transaction::retrieve($id);
      if ($transaction->status == "approved") 
      {
        echo "Paiement effectué";
      }
    } catch (\Exception $e) {
        // Gérer l'erreur ici
        echo "Une erreur s'est produite : " . $e->getMessage();
    }

    

    /* if ($_POST) 
    {
        if (!empty($_POST['id_reservation']) && !empty($_POST['phone_number'])) 
        {
            // Récupérer l'ID depuis la réservation de paiement
            $id_reservation = 2;//intval($_POST["id_reservation"]);
            $phoneNumber = 60524499;//$_POST["phone_number"];

            FedapayPayment();
        }
        else 
        {
            $response=array(
                'status' =>400,
                'status_message' => 'Aucune réservation existe !',
            );
        }
    }
    else 
    {
        $response=array(
            'status' => 400,
            'status_message' => 'Aucune réservation n\'a été crée . Désolé !',
        );
    }
    header('Content-Type: application/json');
    echo json_encode($response); */

?>