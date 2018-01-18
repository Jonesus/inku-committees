<?php
/*-----------------*/
/* Functional code */
/*-----------------*/

global $wpdb;

// Define helper functions
function is_chair($filler_id, $committee_title_fi) {
  global $wpdb;
  $committee_id = $wpdb->get_var("SELECT ID FROM Committees WHERE title_fi='$committee_title_fi';");
  $query = $wpdb->prepare(
    "SELECT c.filler_ID
    FROM Chairs AS c
    WHERE c.filler_ID=%d
    AND c.committee_ID=%d;",
    $filler_id, $committee_id
  );
  $res = $wpdb->get_var($query);
  if ($res) {
    return 'true';
  } else {
    return 'false';
  }
}

function set_as_chair($filler_id, $committee_title_fi) {
  global $wpdb;
  $committee_id = $wpdb->get_var("SELECT ID FROM Committees WHERE title_fi='$committee_title_fi';");

  $query = $wpdb->prepare(
    "INSERT INTO Chairs (filler_ID, committee_ID) VALUES (%d, %d);",
    $filler_id, $committee_id
  );
  $res = $wpdb->get_results($query);
}

function remove_from_chairs($filler_id) {
  global $wpdb;
  $res = $wpdb->get_results("DELETE FROM Chairs WHERE filler_ID=$filler_id;");
}


// If form was submitted
if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
  $action = $_POST['action'];
  unset($_POST['action']);

  if ($action == 'save') {
    $filler_id = $_POST['ID'];
    $position_title_fi = $_POST['position_title_fi'];
    $committee_title_fi = $_POST['committee_title_fi'];
    $year = $_POST['year'];
    $display_name = $_POST['display_name'];
    $user_email = $_POST['user_email'];
    $picture_path = $_POST['picture_path'];
    $is_chairperson = $_POST['is_chairperson'];

    $user_id_name = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix."users WHERE display_name='$display_name';");
    $user_id_email = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix."users WHERE user_email='$user_email';");
    $position_id = $wpdb->get_var("SELECT ID FROM Positions WHERE title_fi='$position_title_fi';");
    $committee_id = $wpdb->get_var("SELECT ID FROM Committees WHERE title_fi='$committee_title_fi';");

    if ($user_id_name != $user_id_email) {
      echo "<h2 style='color: red;'>Error: user name ($display_name) and email ($user_email) don't match the same user!</h2>";
    } elseif (!$position_id) {
      echo "<h2 style='color: red;'>Error: position ($position_title_fi) not found!</h2>";
    } elseif (!$committee_id) {
      echo "<h2 style='color: red;'>Error: committee ($committee_title_fi) not found!</h2>";
    } elseif (!$year) {
      echo "<h2 style='color: red;'>Error: field 'year' not found in submission!</h2>";
    }
    else {
      $user_id = $user_id_name;

      $columns = Array('ID', 'member_ID', 'year', 'position_ID', 'committee_ID', 'picture_path');
      $cols = implode( ',', $columns );
      $query =
        "INSERT INTO Fillers ($cols)
        VALUES ($filler_id, $user_id, $year, $position_id, $committee_id, '$picture_path') 
        ON DUPLICATE KEY UPDATE ";
      foreach ($columns as $col) {
        $query .= "$col=VALUES($col),";
      }
      $query = rtrim($query, ',');
      $query .= ';';
      $res = $wpdb->get_results($query);

      if ($is_chairperson == 'true') {
        set_as_chair($filler_id, $committee_title_fi);
      } else {
        remove_from_chairs($filler_id);
      }

      echo "<h2 style='color: green;'>Updated field with ID $filler_id!</h2>";
    }
  }
  elseif ($action == 'delete') {
    $delete_id = $_POST['ID'];
    remove_from_chairs($delete_id);
    $query = "DELETE FROM Fillers WHERE ID=$delete_id;";
    $res = $wpdb->get_results($query);
    echo "<h2 style='color: red;'>Deleted field with ID $delete_id!</h2>";
  }
} else if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
  if ( is_numeric($_GET['year']) ) {
    $year = $_GET['year'];
  } else {
    $year = date('Y');
  }
}


// Get all column names
$columns = Array(
  "ID",
  "committee_title_fi",
  "position_title_fi",
  "year",
  "display_name",
  "user_email",
  "picture_path",
  "is_chairperson"
);

// Get table
$query = $wpdb->prepare(
  "SELECT
      f.ID,
      c.title_fi AS committee_title_fi,
      p.title_fi AS position_title_fi,
      f.year,
      m.display_name,
      m.user_email,
      f.picture_path
  FROM Fillers AS f
      INNER JOIN ".$wpdb->prefix."users AS m
          ON f.member_ID=m.ID
      INNER JOIN Positions AS p
          ON f.position_ID=p.ID
      INNER JOIN Committees AS c
          ON f.committee_ID=c.ID
  WHERE f.year=%d;",
  $year);
 
$results = $wpdb->get_results($query, ARRAY_A);

// Get chair statuses
foreach (range(0, count($results)-1) as $i) {
  $chair_status = is_chair($results[$i]['ID'], $results[$i]['committee_title_fi']);
  $results[$i]['is_chairperson'] = $chair_status;
}

// Add new empty entry
$last_id = max(array_column($results, 'ID'));
foreach ($columns as $col) {
  $row[$col] = '';
}
$row['ID'] = $last_id+1;
$results[] = $row


/*-----------------*/
/* Visual template */
/*-----------------*/
?>

<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>style.css">

<h1>Filler management</h1>
<h3>Please never change the ID!</h3>
<p>You can change the year by appending "&year=[number]" to the url and pressing enter.</p>
<div class="dataform">
  <div class="datarow">
    <?php foreach ( $columns as $col ) : ?>
      <textarea name="<?php echo $col; ?>" class="data-column" readonly><?php echo $col; ?></textarea>
    <?php endforeach; ?>
  </div><br/><br/><br/><br/>

  <?php foreach ( $results as $row_key => $row ) : ?>
  <div class="datarow">
    <form action="<?php admin_url( "options-general.php?page=".$_GET["page"] ) ?>" method="post" onsubmit="return confirm('Do you really want to do this?');">
      <?php foreach ( $row as $field_key => $field ) : ?>
        <textarea
          name="<?php echo $field_key; ?>"
          class="data-column"
          <?php echo ($field_key == 'ID') ? 'readonly' : ''; ?>
        ><?php echo $field; ?></textarea>
      <?php endforeach; ?>
      <input type="submit" name="action" value="save" style="margin-left: 5px;" />
      <input type="submit" name="action" value="delete" style="margin-left: 5px;" />
    </form>
  </div><br/><br/>
  <?php endforeach; ?>
</div>


