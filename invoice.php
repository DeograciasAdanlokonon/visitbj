<?php

  require_once'database.php';

  require_once('fpdf/fpdf.php');

  //Initialize Response
  $response = "";

  if ($_POST) 
  {
    if (!empty($_POST['id_reservation'])) 
    {
        //Get the reservation ID from payement API
        $id = intval($_POST["id_reservation"]);

        // Se connecter à la base de données
        $bdd = Database::connect();
        
        // Requête sur la base de données
        $statement = $bdd->prepare("SELECT *
                                    FROM user_pack
                                    INNER JOIN users ON user_pack.user_id = users.id
                                    INNER JOIN pack ON user_pack.pack_id = pack.id
                                    WHERE user_pack.id = ?");
        $statement->execute(array($id));
        $data = $statement->fetch();

        //Launch functions if is_reserved = 1
        if ($data['is_reserved'] == 1) 
        {
          GenerateFacture($data);
          SendEmail($data);
          Database::disconnect();
        }
        else 
        {
          $response=array(
            'status' => 0,
            'status_message' =>'Réservation non effectuée! Veuillez d\'abords valider votre paiement sur votre panier.'
          );
        }
    } 
    else 
    {
        $response=array(
          'status' => 0,
          'status_message' =>'Aucune réservation existe !.'
        );
    }
    
  }
  else 
  {
      $response=array(
        'status' => 0,
        'status_message' =>'Pas de paramètres de réservation trouvés.'
      );
  }
  header('Content-Type: application/json');
  echo json_encode($response);

  
 function GenerateFacture($data)
 {
    //Create a new PDF file
    $pdf = new FPDF();

    // Définition des marges
    $margin = 20; // Marge en unités (par défaut : millimètres)
    $pdf->SetMargins($margin,10,$margin,0);

    $date = date('Y-m-d');

    //New page
    $pdf->AddPage('P', 'A4');

    // Set logo image
    $pdf->Image('image/logo-visit-web.jpg', 30, 5, 30);

    // Espacement horizontal
    $pdf->Cell(50); // Espacement de 50 unités

    //company adress
    $pdf->SetFont('Times', '', 10);
    $pdf->SetTextColor(29, 29, 27);
    $pdf->Cell(80, 10, 'APPLICATION WEB DE TOURISME - 312 RUE HAIE VIVE', 0, 0, 'L');
    $pdf->Ln(3);
    $pdf->SetFont('Times', '', 8);
    $pdf->Cell(190, 10, 'Capital de 10 000 000 FCFA - RCCM Cotonou - IFU 0000000000', 0, 0, 'C');
    $pdf->Ln(3);
    $pdf->SetFont('Times', '', 8);
    $pdf->Cell(190, 10, 'Tel: +22900000000 - email: contact@visit.bj', 0, 0, 'C');

    // Espacement vertical
    $pdf->Cell(0, 15, '', 0, 1); // Hauteur de 5 unités, sans contenu

    //Font and color
    $pdf->SetFont('Times', 'B', 10);
    $pdf->SetTextColor(29, 29, 27);

    //Invoice header
    $pdf->Cell(0, 10, 'FACTURE ref: FC-' .$data['reference']  , 0, 1, 'R');

    //Payement details
    $pdf->Cell(0, 20, '', 0, 1); // Hauteur de 20 unités, sans contenu

    // Contenu du texte
    $pdf->SetFont('Times', 'B', 11);
    $pdf->SetTextColor(255, 136, 0);

    // Afficher le texte à l'intérieur du bloc
    $pdf->Cell(80, 7, 'Details du paiement', 1, 0, 'L', false);
    $pdf->Cell(4); // Espacement de 10 unités
    $pdf->Cell(80, 7, 'Client facture', 1, 0, 'L', false);

    $pdf->SetFont('Times', '', 10);
    $pdf->SetTextColor(29, 29, 27);

    $pdf->Ln(10);

    $pdf->Cell(80, 0, 'Paye le: ' .$date , 0, 0, 'L', false);
    $pdf->Cell(5); // Espacement de 10 unités
    $pdf->Cell(80, 0, $data['first_name']. ' ' .$data['last_name'], 0, 0, 'L', false);


    $pdf->Ln(4);

    $pdf->Cell(80, 0, 'Mode reglement: '.$data['mode_paiement'] , 0, 0, 'L', false);
    $pdf->Cell(5); // Espacement de 10 unités
    $pdf->Cell(80, 0, $data['email'] , 0, 0, 'L', false);

    $pdf->Ln(4);

    $pdf->Cell(80, 0, 'Reference: '.$data['reference']  , 0, 0, 'L', false);
    $pdf->Cell(5); // Espacement de 10 unités
    $pdf->Cell(80, 0, $data['phone_number'], 0, 0, 'L', false);


      //Description of the invoice
    $pdf->Cell(0, 32, '', 0, 1); // Hauteur de 5 unités, sans contenu
    $pdf->SetFont('Times', 'B', 11);
    $pdf->SetTextColor(255, 136, 0);

    $pdf->Cell(135, 7, 'Designation', 1, 0, 'L', false);
    //$pdf->Cell(80, 7, 'Prix HT', 1, 'R', false);
    $pdf->Cell(30, 7, 'Prix HT (CFA)', 1, 1, 'R');

    //Pack bought
    $pdf->SetFont('Times', 'I', 10);
    $pdf->SetTextColor(29, 29, 27);

    $pdf->Ln(1);

    $total = $data['price'] * $data['number_participant'];

    $pdf->Cell(145, 7, 'Achat du pack: ' .$data['name']. ' d\'une duree de ' .$data['duration']. ' jour(s) pour ' .$data['number_participant'].  ' personne(s)', 0, 0, 'L', false);
    $pdf->Cell(5); // Espacement de 10 unités
    $pdf->Cell(15, 7, $total, 0, 0, 'R');

    $pdf->Ln(5);

    $pdf->Cell(135, 7, 'Consulter votre Compte ou email pour avoir les details de votre reservation', 0, 'L', false);
    $pdf->Cell(5); // Espacement de 10 unités
    //$pdf->Cell(30, 7, '', 0, 'R');

    $pdf->Ln(5);

    //Total
    $pdf->SetFont('Times', 'B', 12);
    $pdf->SetTextColor(29, 29, 27);

    $pdf->Cell(135, 7, 'Total TTC', 0, 0, 'R');
    $pdf->Cell(30, 7, $total, 0, 0, 'R');


    // POLICIE
    $pdf->Cell(0, 32, '', 0, 1); // Hauteur de 5 unités, sans contenu

    $pdf->SetFont('Times', 'B', 12);
    $pdf->SetTextColor(255, 136, 0);
    $pdf->Cell(135, 7, 'CONDITIONS DE REGELEMENT', 0, 0, 'L');

    $pdf->Ln(5);

    // Position et dimensions du bloc de texte
    $x = 20;  // Position horizontale du bloc
    $y = 163;  // Position verticale du bloc
    $width = 165;  // Largeur du bloc
    $height = 35;  // Hauteur du bloc

    // Dessiner le contour du bloc
    $pdf->Rect($x, $y, $width, $height);

    $pdf->SetFont('Times', '', 10);
    $pdf->SetTextColor(29, 29, 27);
    $text = "Nos prix s'entendent toute remise deduite. Nos factures sont payables comptant a reception et sans escompte.";

    // Afficher le texte à l'intérieur du bloc
    $pdf->Cell(165, 10, $text, 0, 0, 'L');

    $pdf->Ln(6);

    $pdf->Cell(165, 7, 'Consulter votre Compte ou email pour avoir les details de votre reservation.', 0, 0, 'L', false);

    $pdf->SetFont('Times', 'I', 8.5);
    $pdf->SetTextColor(29, 29, 27);
    $pdf->Ln(6);

    $pdf->Cell(165, 7, 'Les conditions generales de ventes (CGV) applicables a votre commande lors de son enregistrement sont disponibles sur notre site.', 0, 1, 'L');

    $pdf->Cell(0, 3, '', 0, 1); // Hauteur de 5 unités, sans contenu

    $pdf->Cell(0, 10, 'Ne pas jeter sur la voir', 0, 0, 'C');

    $pdf->Ln(9);

    // Dessiner un rectangle pour simuler le bouton
    $pdf->SetFillColor(255, 136, 0);
    $pdf->Rect(90, 205, 30, 8, 'F');

    // Ajouter le texte du bouton d'impression
    $pdf->SetFont('Times', 'B', 10);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'Imprimer', 0, 1, 'C');

    // Pied de page
    $pdf->SetY(265);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'www.visit.bj - Page '.$pdf->PageNo(), 0, 0, 'C');

    ob_clean(); // Effacer tout tampon de sortie

    $savePath = 'invoice/'; // Remplacez par le chemin d'accès réel
    $fileName = 'facture-' . $data['reference'] . '.pdf';
    $filePath = $savePath . $fileName;

    // Sortir le document PDF
    $pdf->Output('F', $filePath);
 }

 //Function send email
 function SendEmail($data)
 {
    //Message text of the mail
    $emailText="";
    $emailText .= "Madame, Monsieur " . $data['first_name'] . "\n\n";
    $emailText .="Veuillez trouver en pièce jointe votre facture ayant pour référence: FC-" .$data['reference']." (Fichier: facture-" .$data['reference'].") au format PDF.\n\n";
    $emailText .="Nous vous remercions pour la confiance.\n";
    $emailText .="VISIT.BJ Cotonou Haie Vive Rue 312.\n\n\n";
    $emailText .="NOTE : PLEASE DO NOT REPLY TO THIS EMAIL, NO REPLY WILL BE GIVEN.\n";

    $to = $data['email'];
    $subject = "Votre facture est disponible";
    $from = "VISITE@visit.bj";
    $attachment = 'invoice/facture-' . $data['reference'] . '.pdf';

    // Boundary unique pour séparer les différentes parties de l'e-mail
    $boundary = md5(time());

    // Headers de l'e-mail
    $headers = "From: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Corps de l'e-mail
    $emailBody = "--$boundary\r\n";
    $emailBody .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
    $emailBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $emailBody .= $emailText."\r\n";

    // Lecture du contenu du fichier joint
    $fileContent = file_get_contents($attachment);
    $fileContent = chunk_split(base64_encode($fileContent));

    // Ajout du fichier joint
    $emailBody .= "--$boundary\r\n";
    $emailBody .= "Content-Type: application/pdf; name=\"fichier.pdf\"\r\n";
    $emailBody .= "Content-Transfer-Encoding: base64\r\n";
    $emailBody .= "Content-Disposition: attachment\r\n\r\n";
    $emailBody .= $fileContent."\r\n";
    $emailBody .= "--$boundary--";

    // Envoi de l'e-mail
    $mailSent = mail($to, $subject, $emailBody, $headers);

    if ($mailSent) 
    {
        $response=array(
          'status' => 1,
          'status_message' =>'Votre paiement a été effectué avec succès. Consultez votre compte ou votre email pour avoir les détails de votre réservation.'
        );
    } 
    else 
    {
        echo "Erreur lors de l'envoi de l'e-mail.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);


 }  

?>