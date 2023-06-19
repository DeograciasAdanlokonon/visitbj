<?php

require_once 'database.php';
require 'vendor/autoload.php';
require 'fedapay-php/init.php';

if ($_POST) 
    {
        if (!empty($_POST['id_reservation']) && !empty($_POST['phone_number'])) 
        {
            try 
            {
                // Récupérer l'ID depuis la réservation de paiement
                $id_reservation = intval($_POST["id_reservation"]);
                $phoneNumber = $_POST["phone_number"];

                FedapayPayment($id_reservation, $phoneNumber);
            } 
            catch (\Exception $e) 
            {
                // Gérer l'erreur ici
                echo "Une erreur s'est produite : " . $e->getMessage();
            }
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
    echo json_encode($response);




    // Fonction pour appeler Fedapay
    function FedapayPayment($id_reservation, $phoneNumber)
    {
        // Se connecter à la base de données
        $bdd = Database::connect();

        // Obtenir les informations de l'utilisateur
        $statement = $bdd->prepare("SELECT *
                                FROM user_pack
                                INNER JOIN users ON user_pack.user_id = users.id
                                INNER JOIN pack ON user_pack.pack_id = pack.id
                                WHERE user_pack.id = ?");
        $statement->execute([$id_reservation]);
        $data = $statement->fetch();

        $description = "Paiement de circuit(s) touristique sur www.visit.bj";
        $amount = $data['price'] * $data['number_participant'];
        $currency = 'XOF';
        $lastName = $data['last_name'];
        $firstName = $data['first_name'];
        $email = $data['email'];
        $countryCode = "bj";
        $is_reserved = $data['is_reserved'];
        $mode_paiement = 'METHODE_PAIEMENT'; // 'mtn', 'moov', 'mtn_ci', 'moov_tg'

        // Étape 1
        // Remplacez VOTRE_CLE_API par votre véritable clé API
        \FedaPay\FedaPay::setApiKey("sk_sandbox_MKQovp-HGppu1785kugMgRg1");

        /* Précisez si vous souhaitez exécuter votre requête en mode test ou live */
        \FedaPay\FedaPay::setEnvironment('sandbox'); // ou setEnvironment('live');

        /* Créer la transaction */
        $transaction = \FedaPay\Transaction::create([
            "description" => $description,
            "amount" => $amount,
            "currency" => ["iso" => "XOF"],
            "callback_url" => "http://arenedeo.hecamacb.com/deo/visitbj-invoice/module-invoice.php?id_reservation=" . $id_reservation,
            "customer" => [
                "firstname" => $firstName,
                "lastname" => $lastName,
                "email" => $email,
                "phone_number" => [
                    "number" => $phoneNumber,
                    "country" => $countryCode
                ]
            ]
        ]);

        // Étape 2
        $token = $transaction->generateToken();
        header('Location: ' . $token->url);
        exit();

        // Étape 3
        // Obtenir l'ID de la transaction
        $id = intval($_POST["id"]);

        $transaction = \FedaPay\Transaction::retrieve($id);
        if ($transaction->status == "approved") {
            // Mettre à jour user-pack sur is_reserved et mode_paiement
            if ($is_reserved == 0) {
                $UpdateReservation = $bdd->prepare("UPDATE user_pack SET is_reserved = ?, mode_paiement = ?");
                $UpdateReservation->execute([1, $mode_paiement]);
            }

            Database::disconnect();
        } elseif ($transaction->close == true) {
            $response = [
                'status' => 0,
                'status_message' => 'Paiement annulé !'
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }
?>
