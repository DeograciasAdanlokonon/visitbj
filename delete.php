<?php

  require_once'database.php';

  //Initialize Response
  $response = "";

  if ($_POST) 
  {
    if (!empty($_POST['id_reservation']) && !empty($_POST['user_id'])) 
    {
        //Get the reservation  and user ID
        $id = intval($_POST["id_reservation"]);
        $user_id = intval($_POST["user_id"]);

        //Call Delete function
        DeleteReservation($id);  
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



  function DeleteReservation($id)
  {
      global $id, $user_id;
      // Connecting to Data base
      $bdd = Database::connect();
        
      // Requeste on data base
      $delete = $bdd->prepare("DELETE FROM user_pack WHERE id= ? AND user_id = ?");
      $delete->execute(array($id, $user_id));

      $deleted_reservation = true;
      Database::disconnect();
      
      if ($deleted_reservation) 
      {
        $response=array(
          'status' => 0,
          'status_message' =>'Votre réservation a bien été supprimée. Vous pouvez lancer une nouvelle réservation de circuit.'
        );
      }
      else 
      {
        $response=array(
          'status' => 0,
          'status_message' =>'Echec de la suppression de la réservation.'
        );
      }
      header('Content-Type: application/json');
      echo json_encode($response);
  }
  
?>