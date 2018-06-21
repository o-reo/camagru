<?php
require_once 'pdo.class.php';

class snap extends pdo_connection
{
    function __construct()
    {
        parent::__construct();
    }

    function register_snap($user_id, $filepath)
    {
        $this->connect();
        $st = $this->dbh->prepare("INSERT INTO pics (`user_id`, `path`) VALUES (:user_id, :path)");
        $st->execute(array(':user_id' => $user_id, ':path' => $filepath));
        $id = $this->dbh->prepare("SELECT `id` FROM pics WHERE `path` = :path");
        $id->execute(array(":path" => $filepath));
        $res = $id->fetchAll();
        $this->close();
        return $res[0]['id'];
    }

    function get_images($user, $page)
    {
        if (!is_numeric($page) || $page < -1 || $page == 0)
            return array();
        $var = array();
        $select = "";
        if ($user != 0)
            $select = " WHERE user_id = :user_id ";
        $limit = ";";
        if ($page != -1)
            $limit = " LIMIT 5 OFFSET :previous";
        $this->connect();
        $sentence = "SELECT `id`, `path`, `upload_time`, `user_id` FROM pics ".$select.
            "ORDER BY `upload_time` DESC".$limit;
        $stmnt = $this->dbh->prepare($sentence);
        $previous = intval(5 * ($page - 1));
        if ($user != 0)
            $stmnt->bindParam(':user_id', $user, PDO::PARAM_INT);
        if ($page != -1)
            $stmnt->bindParam(':previous', $previous, PDO::PARAM_INT);
        if (count($var) > 0)
            $stmnt->execute($var);
        else
            $stmnt->execute();
        $res = $stmnt->fetchAll();
        $this->close();
        return $res;
    }

    function count_images()
    {
        $this->connect();
        $ct = $this->dbh->query("SELECT COUNT(*) FROM pics");
        $ct = $ct->fetchAll();
        $this->close();
        return $ct;
    }

    function format_timestamp($timestamp)
    {
        $utc_date = DateTime::createFromFormat(
            'Y-m-d G:i:s',
            $timestamp,
            new DateTimeZone('UTC')
        );

        $local_date = $utc_date;
        $local_date->setTimeZone(new DateTimeZone('Europe/Paris'));
        setlocale (LC_TIME, 'fr_FR');
        return strftime("%H:%M", $local_date->getTimestamp());
    }

    function save(user $user, $img)
    {
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $id = uniqid();
        $file = '../model/pics/' . $id . '.png';
        file_put_contents($file, $data);
        $id = $this->register_snap($user->get_id(),$id.'.png');
        return $id;
    }

    function delete($snap_id)
    {
        $this->connect();
        $st = $this->dbh->prepare("DELETE FROM pics WHERE `id` = :snap_id");
        $st->execute(array(":snap_id" => $snap_id));
        $st = $this->dbh->prepare("DELETE FROM comments WHERE `snap_id` = :snap_id");
        $st->execute(array(":snap_id" => $snap_id));
        $st = $this->dbh->prepare("DELETE FROM likes WHERE `snap_id` = :snap_id");
        $st->execute(array(":snap_id" => $snap_id));
        $this->close();
    }
}