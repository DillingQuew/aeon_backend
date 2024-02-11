<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d) && is_numeric($d) ? $d : 0;
        // where
    
        $where = "user_id='".$user_id."'";

        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'user_id' => (int) $row['user_id'],
                'plot_id' => (string) $row['plot_id'],
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'email' => (string) $row['email'],
                'phone' => (string) $row['phone'],
                'access' => (int) $row['access'],
            ];
        } else {
            return [
              'user_id' => "",
              'plot_id' => "",
              'first_name' => "",
              'last_name' => "",
              'email' => "",
              'phone' => "",
              'access' => "",
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }


    public static function users_list($d = []) {
      // vars
      $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
      $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
      $limit = 20;
      $items = [];
      // where
      $where = [];
      if ($search) $where[] = "phone LIKE '%".$search."%' OR first_name LIKE '%".$search."%' OR email LIKE '%".$search."%'";
      $where = $where ? "WHERE ".implode(" AND ", $where) : "";

      $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email, last_login
          FROM users ".$where." ORDER BY first_name LIMIT ".$offset.", ".$limit.";") or die (DB::error());
      while ($row = DB::fetch_row($q)) {
          $items[] = [
              'user_id' => (int) $row['user_id'],
              'plot_id' => (int) $row['plot_id'],
              'first_name' => $row['first_name'],
              'last_name' => $row['last_name'],
              'phone' => $row['phone'],
              'email' => $row['email'],
              'last_login' => date('Y/m/d', $row['last_login'])
          ];
      }
      // paginator
      $q = DB::query("SELECT count(*) FROM users ".$where.";");
      $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
      $url = 'users?';
      if ($search) $url .= '&search='.$search;
      paginator($count, $offset, $limit, $url, $paginator);
      // output
      return ['items' => $items, 'paginator' => $paginator];
  }

  public static function users_fetch($d = []) {
    $info = User::users_list($d);
    HTML::assign('users', $info['items']);
    return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
  }

  public static function user_edit_window($d = []) {

    $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
    HTML::assign('user', User::user_info($user_id));
    return ['html' => HTML::fetch('./partials/user_edit.html')];
  }

  public static function user_edit_update($d = []) {
    // vars
    $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
    $first_name = isset($d['first_name']) ? $d['first_name'] : "";
    $last_name = isset($d['last_name']) ? $d['last_name'] : "";
    $phone = isset($d['phone']) ? preg_replace('/[^0-9]/', '', $d['phone']) : "";
    $email = isset($d['email']) ? strtolower($d['email']) : "";
    $plot_id = isset($d['plots']) ? $d['plots'] : "";
    $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;

    if ($user_id) {
        $set = [];
        $set[] = "first_name='".$first_name."'";
        $set[] = "last_name='".$last_name."'";
        $set[] = "phone='".$phone."'";
        $set[] = "email='".$email."'";
        $set[] = "updated='".Session::$ts."'";
        $set[] = "plot_id='".$plot_id."'";
        $set = implode(", ", $set);
        DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
    } else {
        DB::query("INSERT INTO users (
            first_name,
            last_name,
            phone,
            email,
            updated
        ) VALUES (
            '".$first_name."',
            '".$last_name."',
            '".$phone."',
            '".$email."',
            '".Session::$ts."'
        );") or die (DB::error());
    }
    // output
    return User::users_fetch(['offset' => $offset]);
}

  public static function user_remove($d) {
    $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
    DB::query("DELETE FROM users WHERE user_id="."$user_id") or die (DB::error());
    return User::users_fetch(['offset' => 0]);
  }

}
