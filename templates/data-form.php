<?php
global $wpdb;
$table_name = $attributes['table_name'];

// If form was submitted
if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
  $action = $_POST['action'];
  unset($_POST['action']);

  if ($action == 'save') {
    $cols = "(".implode(',',array_keys($_POST)).")";
    $values = "(";
    foreach ($_POST as $key => $value) {
      $value = trim($value);
      if (is_numeric($value)) {
        $values .= $value . ",";
      } else {
        $values .= "'".$value."',";
      }
    }
    $values = rtrim($values, ",");
    $values .= ")";

    $query = "INSERT INTO $table_name $cols VALUES $values ON DUPLICATE KEY UPDATE ";
    foreach (array_keys($_POST) as $col) {
      $query .= "$col=VALUES($col),";
    }
    $query = rtrim($query, ',');
    $query .= ';';
    $res = $wpdb->get_results($query);
    $id = $_POST['ID'];
    echo "<h2 style='color: green;'>Updated field with ID $id!</h2>";
  }
  elseif ($action == 'delete') {
    $delete_id = $_POST['ID'];
    $title_fi = $_POST['title_fi'];
    $query = "DELETE FROM $table_name WHERE ID=$delete_id AND title_fi='$title_fi';";
    $res = $wpdb->get_results($query);
    echo "<h2 style='color: red;'>Deleted field with ID $delete_id!</h2>";
  }
}


// Get all column names
$cols = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table_name';", ARRAY_N);
foreach ($cols as $col) {
  $columns[] = $col[0];
}

// Get table
$query = "SELECT * FROM ".$table_name.";";
$results = $wpdb->get_results($query, ARRAY_A);

// Add new empty entry
$last_id = max(array_column($results, 'ID'));
foreach ($columns as $col) {
  $row[$col] = '';
}
$row['ID'] = $last_id+1;
$results[] = $row

?>


<h1><?php echo $table_name ?> management</h1>
<h3>Please never change the ID!</h3>
<div class="dataform">
  <div class="datarow">
    <?php foreach ( $columns as $col ) : ?>
      <textarea name="<?php echo $col; ?>" class="data-column" style="display:inline;float:left;text-align:center;" readonly><?php echo $col; ?></textarea>
    <?php endforeach; ?>
  </div><br/><br/><br/>

  <?php foreach ( $results as $row_key => $row ) : ?>
  <div class="datarow">
    <form action="<?php admin_url( "options-general.php?page=".$_GET["page"] ) ?>" method="post" onsubmit="return confirm('Do you really want to do this?');">
      <?php foreach ( $row as $field_key => $field ) : ?>
        <textarea
          name="<?php echo $field_key; ?>"
          class="data-column"
          style="display:inline;float:left;"
          <?php echo ($field_key == 'ID') ? 'readonly' : ''; ?>
        ><?php echo $field; ?></textarea>
      <?php endforeach; ?>
      <input type="submit" name="action" value="save" style="margin-left: 5px;" />
      <input type="submit" name="action" value="delete" style="margin-left: 5px;" />
    </form>
  </div><br/>
  <?php endforeach; ?>
</div>