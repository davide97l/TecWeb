<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

    <head>
      <meta http-equiv="Content-Type" content="text/html, charset=utf-8" />
      <meta name="description" content="Online artwork database"/>
      <meta name="keywords" content="artwork,picture,image,database"/>
      <meta name="author" content="Daniele Bianchin, Pardeep Singh, Davide Liu, Harwinder Singh"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link rel="stylesheet" href="Style/style.css"/>
      <link rel="stylesheet" href="viewStyle.css"/>
      <script type="text/javascript" src="script.js" ></script>
      <script type="text/javascript" src="imagezoom.js" ></script>
      <title>Artbit</title>
    </head>
    <body onload="magnify(); setResizeListner();" >
      <?php
        require_once "header.php";
        require_once "DbConnector.php";
        require_once "functions.php";

        $Title = $_GET['Title'];
        $Artist = $_GET['Artist'];

        $myDb= new DbConnector();
        $myDb->openDBConnection();
        if($myDb->connected)
        {
         if(isset($Title) && isset($Artist))
         {
          $qrStr = 'SELECT Artista, Nome, Descrizione, Categoria, Data_upload FROM opere WHERE Artista ="'.$Artist.'"'.' AND Nome ="'.$Title.'"';
          $result = $myDb->doQuery($qrStr);
          if(isset($result) && ($result->num_rows === 1))
          {
            $Error= "";
            $row = $result->fetch_assoc();
            $Title = $row['Nome'];
            $Artist = $row['Artista'];
            $Description = $row['Descrizione'];
            $Category = $row['Categoria'];
            $Date = $row['Data_upload'];
            $qrStr = 'SELECT Opera FROM likes WHERE Opera="'.$Title.'"'.' AND Creatore="'.$Artist.'"';
            $Likes = $myDb->doQuery($qrStr)->num_rows;
            $qrStr = 'SELECT Opera FROM commenti WHERE Opera="'.$Title.'"'.' AND Creatore="'.$Artist.'"';
            $Comments = $myDb->doQuery($qrStr)->num_rows;
            $qrStr = 'SELECT Nome, Cognome FROM artisti WHERE Username="'.$Artist.'"';
            $result = $myDb->doQuery($qrStr);
            $row = $result->fetch_assoc();
            $ArtistName = $row['Nome'] . " " . $row['Cognome'];
            $isLiked = false;

            if(isset($_GET['Remove']))
            {
              $ID = $_GET['Remove'];
              $qrstr = "SELECT ID FROM commenti WHERE ID=".$ID;
              if ($_SESSION['Username'] !== 'Admin')
                $qrstr .= " AND Utente='".$_SESSION['Username']."'";
              if($myDb->doQuery($qrstr)->num_rows !== 1)
                $Error = 'Artwork not found or wrong artwork owner';
              else
                 {
                  $qrstr = "DELETE FROM commenti WHERE ID=".$ID;
                  $myDb->doQuery($qrstr);
                 }
            }
            else if(isset($_GET['input-comment']))
            {
              $Comment = htmlspecialchars($_GET['input-comment'], ENT_QUOTES, "UTF-8");
              if(empty(trim($Comment)))
                $Error = 'Empty field!';
              else
              {
                $qrStr = "INSERT INTO `commenti`(`Opera`, `Utente`, `Creatore`, `Commento`) VALUES ('".$Title."','".$_SESSION['Username']."','".$Artist."','".$Comment."')";
                if(!$myDb->doQuery($qrStr))
                  $Error = 'Query failed!';
              }
            }
          }
          if ( is_session_started() === FALSE || (!isset($_SESSION['Username']))){
             $isLiked = false;
           }else if(isset($_SESSION['Username'])){
             $isLiked = boolImageLiked($Artist,$_SESSION['Username'],$Title)['Result'];
           }
          }
          else
             echo "<script> window.location.replace('404.php') </script>";
         }
         else
           echo "<script>alert(\'Database problem!\');</script>";
      ?>
      <div class="container1024">
      <h1 id="artworkTitle"><?php echo $Title; ?></h1>
      <div id="imageAndDescription">
      <!--Lense and image-->
        <div id="imageContainer">
          <div class="img-magnifier-glass" id="glass"></div>
          <img id="myimage" src=<?php echo "'Images/Art/".rawurlencode($Artist)."/".rawurlencode($Title).".jpeg'";?> alt=<?php echo '"'.$Title.'"' ?> />
        </div>
      <!--Description-->
          <div id="description-comments">
            <div class="descriptionTitle">Description</div>
            <div class="imageInfo">
              <?php echo '</br>By: <a href="gallery.php?gallerySearch='.$Artist.'">'.$Artist.'</a></br>' ?>
              Artist: <?php echo $ArtistName; ?></br>
              Uploaded on: <?php echo $Date; ?></br>
              Category: <?php echo $Category; ?></br>
              Comments: <?php echo $Comments; ?>

              <?php
                echo '<input type="hidden" value="'.$Artist.'" name="nameArtist"/></br>';
                echo '<input type="hidden" value="'.$Title.'" name="nameImage"/></br>';
                echo '<div class="wrapper">';
                echo '<div class="width-15">';
                if($isLiked == true){
                  echo '<div class="like-btn like-btn-added" onclick="btnLikeOnClick(this)" id="LikeBtn_1"></div>';
                }else{
                  echo '<div class="like-btn" onclick="btnLikeOnClick(this)" id="LikeBtn_1"></div>';
                }
                echo '  </div>';
                echo '  <div class="width-85">';
                echo '<p class="customLink" id="Likes_1" onclick="btnLikedByOnClick(this)">Likes: '.getLikesByItem($Artist,$Title)['Result'].'</p>';
                echo '  </div></div>';
              ?>            </div>

            <div id="main-description"><?php echo $Description; ?></div>
          </div>
          </div>
          <div id="commentSection" class="container1024">
          <div class="comment" id="topComment">
          <form action="viewArtwork.php" method="get">
          <?php
            if($myDb->connected && isset($_SESSION['Username']))
                echo $_SESSION['Username'];
              else
                echo "Login to add a comment.";
              if(!empty($Error))
                echo ' ('.$Error.') ';
           ?>
           <?php $en = !isset($_SESSION['Username']) ? "disabled=\"disabled\"" : ""; ?>
           <textarea name="input-comment" id="texxt" rows="2" cols="10" <?php echo  $en?>> </textarea>
           <input type="hidden" name="Title" value=<?php echo '"'.$Title.'"' ?>/>
          <input type="hidden" name="Artist" value=<?php echo '"'.$Artist.'"' ?>/>
        <?php
            echo '<input type="submit" value="Send" id="comment-btn" '.$en.'/>';
          ?>
          </form>
          </div>
          <?php
              if($myDb->connected)
              {
                $qrStr = 'SELECT * FROM commenti WHERE Opera ="'.$Title.'" ORDER BY ID DESC';
                $result = $myDb->doQuery($qrStr);
                if(isset($result) && ($result->num_rows > 0))
                {
                  while($row = $result->fetch_assoc())
                  {
                    echo '<div class="comment">';
                    if($row['Utente'] === $_SESSION['Username'] || strtolower($_SESSION['Username']) === 'admin')
                      echo '<div class="delComment"> <a href="viewArtwork.php?Remove='.$row['ID'].'&Title='.$Title.'&Artist='.$Artist.'"> x </a></div>';
                    echo '<a href="gallery.php?gallerySearch='.$row['Utente'].'">'.$row['Utente'].'</a>';
                    echo ' '.$row['Commento']."</div>"; 
                  }
                }
              }
            ?>
        </div>
    </div>
  </div>
      <?php require_once "footer.html"?>
    </body>
</html>
