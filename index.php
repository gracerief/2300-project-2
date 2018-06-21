<?php


// open connection to database
$db = new PDO('sqlite:data.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function exec_sql_query($db, $sql, $params) {
  $query = $db->prepare($sql);
  if ($query and $query->execute($params)) {
    return $query;
  }
  return NULL;
}

$messages = array();

const SEARCH_FIELDS = [
  "name" => "By Name",
  "nid" => "By National Index Number",
  "type" => "By Type",
  "gen" => "By Generation Number"
];

const TYPE_LIST = [
  "bug" => "bugIC.png",
  "dark" => "darkIC.png",
  "dragon" => "dragonIC.png",
  "electric" => "electricIC.png",
  "fairy" => "fairyIC.png",
  "fighting" => "fightingIC.png",
  "fire" => "fireIC.png",
  "flying" => "flyingIC.png",
  "ghost" => "ghostIC.png",
  "grass" => "grassIC.png",
  "ground" => "groundIC.png",
  "ice" => "iceIC.png",
  "normal" => "normalIC.png",
  "poison" => "poisonIC.png",
  "psychic" => "psychicIC.png",
  "rock" => "rockIC.png",
  "steel" => "steelIC.png",
  "water" => "waterIC.png"
];

const GEN_LIST = [
  1 => "I",
  2 => "II",
  3 => "III",
  4 => "IV",
  5 => "V",
  6 => "VI",
  7 => "VII",
  0 => "0"
];

$all_ids = exec_sql_query($db, "SELECT DISTINCT nind FROM pokemon", NULL)->fetchAll(PDO::FETCH_COLUMN);

//code for the filter functionalities with provided options
if (isset($_GET['filter'])) {
  if (isset($_GET['type'])) {
    $do_search = TRUE;
    $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    if (in_array($type, array_keys(TYPE_LIST))) {
      $filter_field = "type";
    } else {
      $do_search = FALSE;
      array_push($messages, "Invalid Type specified. <?php echo $type;?>");
    };
    $filter = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
  } else if (isset($_GET['gen'])) {
    $do_search = TRUE;
    $gen = filter_input(INPUT_GET, 'filter', FILTER_VALIDATE_INT);
    if (in_array($gen, array_keys(GEN_LIST))) {
        $filter_field = "gen";
    } else {
      $do_search = FALSE;
      array_push($messages, "Invalid Generation specified.");
    };

    $filter = filter_input(INPUT_GET, 'gen', FILTER_VALIDATE_INT);
  }
} else if (isset($_GET['search'])) {
    $input = filter_input(INPUT_GET, 'search');
    if (ctype_digit($input)) {
      $do_search = TRUE;
      $nind = filter_input(INPUT_GET, 'search', FILTER_VALIDATE_INT);
      if (!($nind>0 and $nind<=999)) {
        $do_search = FALSE;
        array_push($messages, "NInd search invalid.");
      };
      $filter_field = "nind";
      $search = filter_input(INPUT_GET, 'search', FILTER_VALIDATE_INT);
    } else if (ctype_alpha($input)) {
        $do_search = TRUE;
        $name = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
        $filter_field = "name";
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
        $search = trim($search);
    } else {
      $do_search = FALSE;
      array_push($messages, "Invalid search entry.");
    }
  } else {
    $do_search = FALSE;
    $search = NULL;
    $gen = NULL;
    $filter = NULL;
};


$monsters = exec_sql_query($db, "SELECT DISTINCT name FROM pokemon", NULL)->fetchAll(PDO::FETCH_COLUMN);
$all_ids = exec_sql_query($db, "SELECT DISTINCT nind FROM pokemon", NULL)->fetchAll(PDO::FETCH_COLUMN);


//code for "Add New Pokémon" functionality
if (isset($_POST["submit_insert"])) {
  $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $nind = next_nind($db, $all_ids);
  $type1 = filter_input(INPUT_POST, 'type1');
  $type2 = filter_input(INPUT_POST, 'type2'); //might have to adjust for NULL type2
  $gen = 0; //
  $image = "default.png";

  $invalid_gen = FALSE;
  $invalid_name = FALSE;
  $invalid_type = FALSE;
  $invalid_nind = FALSE;

  if (!($gen==0)) {
    $invalid_gen = TRUE;
    array_push($messages, "Invalid generation number.");
  }
  if (!(in_array($type1, array_keys(TYPE_LIST))) or (!(in_array($type2, array_keys(TYPE_LIST))) and !($type2==NULL)) or ($type1==NULL)) {
    $invalid_type = TRUE;
    array_push($messages, "Invalid type entry(s).");
  }
  if (in_array($name, $monsters)) {
    $invalid_name = TRUE;
    array_push($messages, "Invalid name input.");
  }
  if (in_array($nind, $all_ids)) {
    $invalid_nind = TRUE;
    $nind= $nind+1;
    array_push($messages, "Invalid NInd.");
  }
  if (!(ctype_alpha($name))) {
    $invalid_name = TRUE;
    array_push($messages, "Invalid name input.");
  }

  if (!($invalid_gen) and !($invalid_type) and !($invalid_name) and !($invalid_nind)) {
    $sql = "INSERT INTO pokemon (nind, name, type1, type2, gen, image) VALUES (:nind, :name, :type1, :type2, :gen, :image)";

    $params = array(
      ':nind' => $nind,
      ':name' => $name,
      ':type1' => $type1,
      ':type2' => $type2,
      ':gen' => $gen,
      ':image' => $image
    );

    $result = exec_sql_query($db, $sql, $params);

    if ($result) {
      array_push($messages, "Your entry has been added to the PokéDex!");
    } else {
      array_push($messages, "Failed to add enty to PokéDex.");
    }
  }
}

function full_nind($nind) {
  if ($nind<10) {
    return ("00" . $nind);
  } else if ($nind<100) {
    return ("0" . $nind);
  } else {
    return ($nind);
  }
}

function print_monster($record) {
  ?>
  <div class="entry">
    <div class="entry_header">
      <h3 class="entry_number">#<?php echo full_nind($record["nind"]);?></h3>
      <h3 class="entry_name"><?php echo htmlspecialchars($record["name"]);?></h3>
    </div>
    <img class="monster_icon" src="images/icons/<?php echo htmlspecialchars($record["image"]);?>" alt="<?php echo htmlspecialchars($record["name"]);?> Icon">
    <div class="entry_types">
      <img class="entry_type_icon" src="images/types/<?php echo htmlspecialchars(TYPE_LIST[$record["type1"]]);?>" alt="Primary Type: <?php echo htmlspecialchars($record["type1"])?>">
      <?php
      if (!($record["type2"]==NULL)) {
        ?>
        <img class="entry_type_icon" src="images/types/<?php echo htmlspecialchars(TYPE_LIST[$record["type2"]]);?>" alt="Secondary Type: <?php echo htmlspecialchars($record["type2"])?>">
        <?php
      }
       ?>
    </div>
  </div>
  <?php
}

$all_ids = exec_sql_query($db, "SELECT DISTINCT nind FROM pokemon", NULL)->fetchAll(PDO::FETCH_COLUMN);

function next_nind($db, $all_ids) {
  return max($all_ids)+1;
}

 ?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
  <link href="https://fonts.googleapis.com/css?family=Prosto+One" rel="stylesheet">

  <title>PokéDex</title>
</head>

<body>
<div id="headings">
<h1><a href="index.php">PokéDex</a></h1>

<!--START FILTERING / SEARCH FORMS-->
<div id="top_forms">

 <!--START Filter Form-->
<form id="filter_form" action="index.php" method="get">

  <h4 id="filter_title">Filter by:</h4>
  <label class="filter_label">Type</label>
    <select name="type">
      <option value="" selected disabled>filter by type</option>
      <?php
      foreach(TYPE_LIST as $type_name => $type_img) {
        ?>
        <option value="<?php echo htmlspecialchars($type_name);?>"><?php echo htmlspecialchars($type_name);?></option>
        <?php
      }
       ?>
    </select><br>

    <label class="filter_label">Generation</label>
    <select name="gen">
      <option value=null selected disabled>filter by generation #</option>
      <?php
      foreach(GEN_LIST as $gen_num => $gen_roman) {
        ?>
        <option value="<?php echo htmlspecialchars($gen_num);?>"><?php echo htmlspecialchars($gen_roman);?></option>
        <?php
      }
       ?>
    </select><br>

    <button type="submit" name="filter">View Filter Results</button>
</form><br>
<!--END Filter Form-->

<!--START Search Form-->
<form id="search_form" action="index.php" method="get">
    <label id="search_label"><strong>Search</strong> by Name or National Index Number ("NInd"):</label><br>
      <input type="text" name="search"/>
    <button type="submit">View Search Results</button>
    <br><h6><a href="index.php/#new_entry_form">Create Your Own Pokémon!</a></h6>
</form>
<!--END Search Form-->

</div> <!--END FILTERING / SEARCHING FORMS-->

</div> <!--END Headings (h1 and forms)-->

<!--ERROR + SUCCESS MESSAGES-->
<?php
foreach ($messages as $message) {
  echo "<p class=\"message\"><strong>" . htmlspecialchars($message) . "</strong></p>\n";
}
 ?>




<!--START Page Content-->
<div id="entry_results">

<?php
  if ($do_search) {

    // Determining Content/Entry Outputs //

    if ($filter_field=='type') {
        $sql = "SELECT * FROM pokemon WHERE type1= :filter OR type2= :filter ORDER BY nind;";
        $header = "Search Results for Type $filter";
    } else if ($filter_field=='gen') {
        $sql = "SELECT * FROM pokemon WHERE " . $filter_field . " = :filter ORDER BY nind;";
        $header = "Search Results for Generation $filter";
    } else if ($filter_field=='name') {
        $sql = "SELECT * FROM pokemon WHERE " . $filter_field . " LIKE '%' || :search || '%' ORDER BY nind;";
        $header = "Search Results by Name \"$search\"";
    } else if ($filter_field=='nind') {
        $sql = "SELECT * FROM pokemon WHERE " . $filter_field . " LIKE :search ORDER BY nind;";
        $header = "Search Results by National Index Number \"$search\"";
    }

    ?>
    <h2><?php echo htmlspecialchars($header);?></h2>
    <?php

    if (isset($filter)) {
      $params= array(
        ':filter' => $filter
      );
    } else {
      $params= array(
        ':search' => $search
      );
    }
  } else {
    ?>
    <h2>All Entries</h2>
    <?php
    $sql = "SELECT * FROM pokemon ORDER BY nind";
    $params = array();
  }

  // ENTRY PRINTING //
  $records = exec_sql_query($db, $sql, $params)->fetchAll();
  if (isset($records) and !empty($records)) {
    foreach ($records as $monster) {
      print_monster($monster);
    }
  } else {
    echo "<p>No entry results.</p>";
  };
 ?>
</div>

<div id="new_entry_form">
  <form id="new_entry" action="index.php" method="post">
    <h3>Create Your Own Pokémon!</h3>
    <ul>
      <li>
        <label>Name</label>
        <input type="text" name="name" required>
        <p class="asterisk">*alphabetic (a-z, A-Z) characters only*</p>
      </li>

      <li>
        <label>Primary Type</label>
        <select required name="type1">
          <option value="" selected disabled>add primary type</option>
          <?php
          foreach(TYPE_LIST as $type_name => $type_img) {
            ?>
            <option value="<?php echo htmlspecialchars($type_name);?>"><?php echo htmlspecialchars($type_name);?></option>
            <?php
          }
           ?>
        </select>
      </li>

      <li>
        <label>Secondary Type</label>
        <select name="type2">
          <option value=NULL selected disabled>add secondary type</option>
          <?php
          foreach(TYPE_LIST as $type_name => $type_img) {
            ?>
            <option value="<?php echo htmlspecialchars($type_name);?>"><?php echo htmlspecialchars($type_name);?></option>
            <?php
          }
           ?>
        </select>
        <p class="asterisk">(optional)</p>
        </li>

      <li id="traits">
        <h5>Automatic traits:</h5>
          <p>Generation: <strong>0</strong></p>
          <p>Image/Icon: <img src="images/icons/default.png" alt="Default Pokémon Icon"></p>
          <p>National Index ID: <strong><?php echo htmlspecialchars(next_nind($db, $all_ids))?></strong></p>
      </li>
      <li>
        <button name="submit_insert" type="submit">Add Pokémon</button>
      </li>
    </ul>
  </form>
</div>


</body>
</html>
